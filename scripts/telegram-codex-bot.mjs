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

if (!token) {
  console.error("Missing TELEGRAM_BOT_TOKEN in .env");
  process.exit(1);
}

const bot = new Telegraf(token);
let running = false;

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

function runCodex(task) {
  return new Promise((resolveRun) => {
    const args = [
      "exec",
      "--cd",
      repoRoot,
      "--sandbox",
      codexSandbox,
      "--ask-for-approval",
      "never",
    ];

    if (codexModel) {
      args.push("--model", codexModel);
    }

    args.push(task);

    const child = spawn(codexBin, args, {
      cwd: repoRoot,
      env: process.env,
      stdio: ["ignore", "pipe", "pipe"],
    });

    let output = "";
    const append = (chunk) => {
      output += chunk.toString();
      if (output.length > maxOutputChars * 2) {
        output = output.slice(-maxOutputChars * 2);
      }
    };

    child.stdout.on("data", append);
    child.stderr.on("data", append);

    child.on("error", (error) => {
      resolveRun({
        ok: false,
        output: `Codex start failed: ${error.message}`,
      });
    });

    child.on("close", (code) => {
      const tail =
        output.length > maxOutputChars
          ? `...ตัด output ช่วงต้นออก...\n${output.slice(-maxOutputChars)}`
          : output;

      resolveRun({
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

  const task = rawText.replace(/^\/task(@\w+)?\s*/i, "").trim();
  if (!task) {
    return ctx.reply("ส่งแบบนี้: /task แก้ปุ่ม submit ให้ disable ตอน loading");
  }

  if (running) {
    return ctx.reply("มีงาน Codex กำลังรันอยู่ รอให้งานก่อนจบแล้วค่อยส่งใหม่");
  }

  running = true;
  await ctx.reply("รับงานแล้ว กำลังให้ Codex ทำงาน...");

  try {
    console.log(`Running task from chat ${chatId(ctx)}: ${task}`);
    const result = await runCodex(task);
    const status = result.ok ? "เสร็จแล้ว" : "Codex จบด้วย error";
    await replyLong(ctx, `${status}\n\n${result.output}`);
  } finally {
    running = false;
  }
}

bot.start((ctx) => {
  console.log(`Received /start from chat ${chatId(ctx)}`);
  ctx.reply(
    [
      "พร้อมรับคำสั่ง Codex แล้ว",
      "",
      "คำสั่ง:",
      "/id ดู chat_id",
      "/task <งานที่ต้องการให้แก้โค้ด>",
      "",
      "ต้องตั้ง TELEGRAM_ALLOWED_CHAT_IDS ให้ตรงกับ chat_id ก่อนถึงจะรัน /task ได้",
    ].join("\n"),
  );
});

bot.command("id", (ctx) => {
  console.log(`Received /id from chat ${chatId(ctx)}`);
  ctx.reply(`chat_id: ${chatId(ctx)}`);
});

bot.command("task", async (ctx) => {
  return handleTask(ctx, ctx.message.text);
});

bot.on("text", async (ctx) => {
  const text = ctx.message.text ?? "";
  console.log(`Received text from chat ${chatId(ctx)}: ${text.slice(0, 80)}`);

  if (/^\/task(@\w+)?/i.test(text)) {
    return handleTask(ctx, text);
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
