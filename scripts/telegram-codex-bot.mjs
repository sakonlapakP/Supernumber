import "dotenv/config";
import { spawn } from "node:child_process";
import { fileURLToPath } from "node:url";
import { dirname, resolve } from "node:path";
import { Telegraf } from "telegraf";

const __dirname = dirname(fileURLToPath(import.meta.url));
const repoRoot = resolve(__dirname, "..");

const token = process.env.TELEGRAM_BOT_TOKEN;
const allowedChatIds = new Set(
  (process.env.TELEGRAM_ALLOWED_CHAT_IDS ?? "")
    .split(",")
    .map((value) => value.trim())
    .filter(Boolean),
);

const codexBin =
  process.env.CODEX_BIN || "/Applications/Codex.app/Contents/Resources/codex";
const codexModel = process.env.TELEGRAM_CODEX_MODEL;
const codexSandbox = process.env.TELEGRAM_CODEX_SANDBOX || "workspace-write";
const maxOutputChars = Number(process.env.TELEGRAM_MAX_OUTPUT_CHARS || 3500);
const codexTimeoutMs = Number(process.env.TELEGRAM_CODEX_TIMEOUT_MS || 600000);

if (!token) {
  console.error("Missing TELEGRAM_BOT_TOKEN in .env");
  process.exit(1);
}

process.on("uncaughtException", (error) => {
  console.error("Uncaught exception", error);
});

process.on("unhandledRejection", (reason) => {
  console.error("Unhandled rejection", reason);
});

process.on("exit", (code) => {
  console.error(`Telegram bot process exiting with code ${code}`);
});

const bot = new Telegraf(token);
let running = false;
let activeTask = null;
const processedTaskMessages = new Set();

function chatId(ctx) {
  return String(ctx.chat?.id ?? "");
}

function isAllowed(ctx) {
  const id = chatId(ctx);
  return allowedChatIds.size > 0 && allowedChatIds.has(id);
}

function replyLong(ctx, text) {
  const trimmed = text.trim() || "(ไม่มี output)";
  const chunks = trimmed.match(/[\s\S]{1,3900}/g) ?? [trimmed];
  return chunks.reduce(
    (promise, chunk) => promise.then(() => ctx.reply(chunk)),
    Promise.resolve(),
  );
}

function taskFromText(text) {
  const match = text.match(/(?:^|\s)\/task(?:@\w+)?\s+([\s\S]*)/i);
  return match?.[1]?.trim() ?? "";
}

function previewTask(task, maxLength = 500) {
  const normalized = task.replace(/\s+/g, " ").trim();
  return normalized.length > maxLength
    ? `${normalized.slice(0, maxLength)}...`
    : normalized;
}

function activeTaskMessage() {
  if (!activeTask) {
    return "ตอนนี้ไม่มีงาน Codex ที่กำลังรันอยู่";
  }

  const elapsedMs = Date.now() - activeTask.startedAt.getTime();
  const elapsedMinutes = Math.max(1, Math.round(elapsedMs / 60000));

  return [
    "ยังทำงานก่อนหน้าอยู่",
    "",
    `เริ่มเมื่อ: ${activeTask.startedAt.toLocaleString("th-TH", {
      timeZone: "Asia/Bangkok",
      hour12: false,
    })}`,
    `รันมาแล้วประมาณ: ${elapsedMinutes} นาที`,
    "",
    "งานที่ค้าง:",
    previewTask(activeTask.task),
  ].join("\n");
}

function helpMessage() {
  return [
    "คำสั่งที่ใช้ได้:",
    "",
    "/task <งาน> สั่งให้ Codex ทำงาน",
    "/status ดูว่างานกำลังรันอยู่ไหม",
    "/cancel ยกเลิกงานที่กำลังรัน",
    "/id ดู chat_id",
    "/help ดูคำสั่งทั้งหมด",
    "",
    "ถ้ามีงานกำลังรันอยู่ /task ใหม่จะไม่ทับงานเดิม",
  ].join("\n");
}

function taskMessageKey(ctx) {
  const message = ctx.message ?? ctx.editedMessage;
  const id = message?.message_id;
  return id ? `${chatId(ctx)}:${id}` : null;
}

function killProcessGroup(pid) {
  try {
    process.kill(-pid, "SIGTERM");
    return true;
  } catch (groupError) {
    try {
      process.kill(pid, "SIGTERM");
      return true;
    } catch (processError) {
      console.error("Failed to cancel Codex process", {
        groupError,
        processError,
      });
      return false;
    }
  }
}

function runCodex(task) {
  return new Promise((resolveRun) => {
    const args = [
      "exec",
      "--cd",
      repoRoot,
      "--sandbox",
      codexSandbox,
    ];

    if (codexModel) {
      args.push("--model", codexModel);
    }

    args.push(task);

    const child = spawn(codexBin, args, {
      cwd: repoRoot,
      detached: true,
      env: process.env,
      stdio: ["ignore", "pipe", "pipe"],
    });
    console.log(`Started Codex pid ${child.pid ?? "unknown"}`);
    if (activeTask) {
      activeTask.pid = child.pid;
    }

    let output = "";
    let settled = false;
    const finish = (result) => {
      if (settled) {
        return;
      }
      settled = true;
      clearTimeout(timeout);
      resolveRun(result);
    };
    const append = (chunk) => {
      output += chunk.toString();
      if (output.length > maxOutputChars * 2) {
        output = output.slice(-maxOutputChars * 2);
      }
    };

    child.stdout.on("data", append);
    child.stderr.on("data", append);

    const timeout = setTimeout(() => {
      child.kill("SIGTERM");
      finish({
        ok: false,
        output: `Codex timed out after ${Math.round(codexTimeoutMs / 1000)} seconds\n\n${output}`,
      });
    }, codexTimeoutMs);

    child.on("error", (error) => {
      console.error("Codex start failed", error);
      finish({
        ok: false,
        output: `Codex start failed: ${error.message}`,
      });
    });

    child.on("close", (code) => {
      console.log(`Codex exited with code ${code}`);
      const tail =
        output.length > maxOutputChars
          ? `...ตัด output ช่วงต้นออก...\n${output.slice(-maxOutputChars)}`
          : output;

      finish({
        ok: code === 0,
        output: tail || `Codex exited with code ${code}`,
      });
    });
  });
}

async function handleTask(ctx, rawText) {
  if (!isAllowed(ctx)) {
    return ctx.reply(
      `ยังไม่ได้อนุญาต chat นี้\nchat_id: ${chatId(ctx)}\nใส่ค่านี้ใน TELEGRAM_ALLOWED_CHAT_IDS แล้ว restart bot`,
    );
  }

  const task = taskFromText(rawText);
  if (!task) {
    return ctx.reply("ส่งแบบนี้: /task แก้ปุ่ม submit ให้ disable ตอน loading");
  }

  const key = taskMessageKey(ctx);
  if (key && processedTaskMessages.has(key)) {
    return ctx.reply("ข้อความ /task นี้เคยเริ่มรันไปแล้ว ถ้าจะสั่งใหม่ให้ส่งเป็นข้อความใหม่");
  }

  if (running) {
    return ctx.reply(activeTaskMessage());
  }

  if (key) {
    processedTaskMessages.add(key);
  }

  running = true;
  activeTask = {
    chatId: chatId(ctx),
    messageId: (ctx.message ?? ctx.editedMessage)?.message_id,
    pid: null,
    startedAt: new Date(),
    task,
  };

  try {
    await ctx.reply("รับงานแล้ว กำลังให้ Codex ทำงาน...");
    console.log(`Running task from chat ${chatId(ctx)}: ${task}`);
    const result = await runCodex(task);
    const status = result.ok ? "เสร็จแล้ว" : "Codex จบด้วย error";
    console.log(`Sending result to chat ${chatId(ctx)}: ${status}`);
    await replyLong(ctx, `${status}\n\n${result.output}`);
  } catch (error) {
    console.error("Task handler failed", error);
    await ctx.reply(`Bot error: ${error.message}`).catch(() => {});
  } finally {
    running = false;
    activeTask = null;
  }
}

async function handleStatus(ctx) {
  if (!isAllowed(ctx)) {
    return ctx.reply(`ยังไม่ได้อนุญาต chat นี้\nchat_id: ${chatId(ctx)}`);
  }

  if (!running) {
    return ctx.reply("ตอนนี้ไม่มีงาน Codex ที่กำลังรันอยู่");
  }

  return ctx.reply(activeTaskMessage());
}

async function handleCancel(ctx) {
  if (!isAllowed(ctx)) {
    return ctx.reply(`ยังไม่ได้อนุญาต chat นี้\nchat_id: ${chatId(ctx)}`);
  }

  if (!running || !activeTask) {
    return ctx.reply("ตอนนี้ไม่มีงาน Codex ที่ต้องยกเลิก");
  }

  const pid = activeTask.pid;
  if (!pid) {
    return ctx.reply("งานกำลังเริ่มต้น ยังไม่มี process id ให้ยกเลิก ลอง /cancel อีกครั้งในอีกไม่กี่วินาที");
  }

  const cancelled = killProcessGroup(pid);
  return ctx.reply(
    cancelled
      ? `ส่งคำสั่งยกเลิกงานแล้ว\n\nงานที่ยกเลิก:\n${previewTask(activeTask.task)}`
      : "ยกเลิกไม่สำเร็จ ดู log ในเครื่องเพื่อเช็ก process",
  );
}

bot.start((ctx) => {
  console.log(`Received /start from chat ${chatId(ctx)}`);
  ctx.reply(["พร้อมรับคำสั่ง Codex แล้ว", "", helpMessage()].join("\n"));
});

bot.command("help", (ctx) => {
  console.log(`Received /help from chat ${chatId(ctx)}`);
  return ctx.reply(helpMessage());
});

bot.command("id", async (ctx) => {
  console.log(`Received /id from chat ${chatId(ctx)}`);
  await ctx.reply(`chat_id: ${chatId(ctx)}`);

  if (taskFromText(ctx.message.text ?? "")) {
    return handleTask(ctx, ctx.message.text);
  }

  return undefined;
});

bot.command("task", async (ctx) => {
  return handleTask(ctx, ctx.message.text);
});

bot.command("status", (ctx) => {
  console.log(`Received /status from chat ${chatId(ctx)}`);
  return handleStatus(ctx);
});

bot.command("cancel", (ctx) => {
  console.log(`Received /cancel from chat ${chatId(ctx)}`);
  return handleCancel(ctx);
});

bot.on("text", async (ctx) => {
  const text = ctx.message.text ?? "";
  console.log(`Received text from chat ${chatId(ctx)}: ${text.slice(0, 80)}`);

  if (taskFromText(text)) {
    return handleTask(ctx, text);
  }

  return undefined;
});

bot.on("edited_message", async (ctx) => {
  const text = ctx.editedMessage?.text ?? "";
  console.log(
    `Received edited message from chat ${chatId(ctx)}: ${text.slice(0, 80)}`,
  );

  if (taskFromText(text)) {
    return handleTask(ctx, text);
  }

  if (/\/status(?:@\w+)?(?:\s|$)/i.test(text)) {
    return handleStatus(ctx);
  }

  if (/\/cancel(?:@\w+)?(?:\s|$)/i.test(text)) {
    return handleCancel(ctx);
  }

  if (/\/help(?:@\w+)?(?:\s|$)/i.test(text)) {
    return ctx.reply(helpMessage());
  }

  return undefined;
});

bot.catch((error, ctx) => {
  console.error("Telegram bot error", error);
  ctx.reply("Bot error: " + error.message).catch(() => {});
});

bot.launch();
console.log("Telegram Codex bot is running");
console.log(`Repo: ${repoRoot}`);

process.once("SIGINT", () => bot.stop("SIGINT"));
process.once("SIGTERM", () => bot.stop("SIGTERM"));
