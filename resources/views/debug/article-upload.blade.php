<!doctype html>
<html lang="th">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Article Upload Debug</title>
    <style>
      body {
        margin: 0;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        background: #f5f7fb;
        color: #1f2937;
      }
      .wrap {
        max-width: 1100px;
        margin: 0 auto;
        padding: 24px;
      }
      .card {
        background: #fff;
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        padding: 20px;
        margin-top: 16px;
      }
      h1 {
        margin: 0 0 8px;
        font-size: 28px;
      }
      p {
        margin: 0;
        color: #6b7280;
      }
      label {
        display: block;
        font-weight: 700;
        margin-bottom: 8px;
      }
      input[type="file"],
      input[type="text"] {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #cfd8e7;
        border-radius: 12px;
        font: inherit;
        box-sizing: border-box;
      }
      button {
        margin-top: 12px;
        border: 0;
        border-radius: 12px;
        padding: 12px 16px;
        background: #223a63;
        color: #fff;
        font: inherit;
        cursor: pointer;
      }
      pre {
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
        background: #0f172a;
        color: #e2e8f0;
        padding: 16px;
        border-radius: 12px;
        overflow: auto;
      }
      .grid {
        display: grid;
        gap: 16px;
      }
      .muted {
        color: #6b7280;
        font-size: 14px;
      }
      .ok {
        color: #0f766e;
        font-weight: 700;
      }
      .warn {
        color: #b45309;
        font-weight: 700;
      }
    </style>
  </head>
  <body>
    <div class="wrap">
      <h1>Article Upload Debug</h1>
      <p>ใช้หน้านี้ตรวจว่า request POST เข้า Laravel จริงไหม และ session/cookie ของแอดมินมาถึงหรือไม่</p>

      <div class="card">
        <form method="get" action="{{ url('/__debug/article-upload') }}">
          <label for="token-get">Token</label>
          <input id="token-get" type="text" name="token" value="{{ $token }}" />
          <button type="submit">Reload</button>
        </form>
      </div>

      <div class="card">
        <form method="post" action="{{ url('/__debug/article-upload') }}?token={{ urlencode($token) }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}" />
          <div class="grid">
            <div>
              <label for="debug_image">ทดสอบอัปโหลดรูป</label>
              <input id="debug_image" type="file" name="debug_image" accept="image/*" />
              <div class="muted">ถ้ากดส่งแล้วขึ้น result แปลว่า Laravel รับ POST และรับไฟล์ได้</div>
            </div>
            <div>
              <label for="note">หมายเหตุ</label>
              <input id="note" type="text" name="note" placeholder="optional" />
            </div>
          </div>
          <button type="submit">Submit Debug Request</button>
        </form>
      </div>

      <div class="card">
        <div class="ok">Diagnostics</div>
        <pre>{{ json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
      </div>

      <div class="card">
        <div class="warn">How to read</div>
        <p class="muted" style="margin-top: 8px;">
          ถ้า `session_authenticated` เป็น false หรือ cookie_names ว่าง แปลว่า browser/เซิร์ฟเวอร์ไม่ได้ส่ง session เข้า Laravel
          ถ้า POST ถึงหน้านี้แล้ว diagnostics เปลี่ยน แปลว่า request ไม่ได้โดน web server บล็อกก่อนถึง PHP
        </p>
      </div>
    </div>
  </body>
</html>
