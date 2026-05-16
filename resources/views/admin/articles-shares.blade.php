@extends('admin-layout')

@section('content')
<div style="padding: 20px; background: #fff; border-radius: 8px; margin-bottom: 20px;">
  <h1 style="margin: 0 0 20px 0; font-size: 24px; font-weight: 600;">📊 สถิติการแชร์บทความ</h1>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 30px;">
    @php
      $totalShares = 0;
      $platformTotals = ['facebook' => 0, 'twitter' => 0, 'line' => 0, 'whatsapp' => 0, 'copy' => 0];

      foreach ($articles as $article) {
        foreach ($article->shares as $share) {
          $totalShares += $share->count ?? 0;
          if (isset($platformTotals[$share->platform])) {
            $platformTotals[$share->platform] += $share->count ?? 0;
          }
        }
      }
    @endphp

    <div style="padding: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px;">
      <div style="font-size: 12px; opacity: 0.9;">รวมการแชร์ทั้งหมด</div>
      <div style="font-size: 32px; font-weight: 700; margin-top: 8px;">{{ $totalShares }}</div>
    </div>

    <div style="padding: 16px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 8px;">
      <div style="font-size: 12px; opacity: 0.9;">Facebook</div>
      <div style="font-size: 32px; font-weight: 700; margin-top: 8px;">{{ $platformTotals['facebook'] }}</div>
    </div>

    <div style="padding: 16px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border-radius: 8px;">
      <div style="font-size: 12px; opacity: 0.9;">X/Twitter</div>
      <div style="font-size: 32px; font-weight: 700; margin-top: 8px;">{{ $platformTotals['twitter'] }}</div>
    </div>

    <div style="padding: 16px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; border-radius: 8px;">
      <div style="font-size: 12px; opacity: 0.9;">LINE</div>
      <div style="font-size: 32px; font-weight: 700; margin-top: 8px;">{{ $platformTotals['line'] }}</div>
    </div>

    <div style="padding: 16px; background: linear-gradient(135deg, #25d366 0%, #20ba5a 100%); color: white; border-radius: 8px;">
      <div style="font-size: 12px; opacity: 0.9;">WhatsApp</div>
      <div style="font-size: 32px; font-weight: 700; margin-top: 8px;">{{ $platformTotals['whatsapp'] }}</div>
    </div>

    <div style="padding: 16px; background: linear-gradient(135deg, #8b5a1f 0%, #6f4815 100%); color: white; border-radius: 8px;">
      <div style="font-size: 12px; opacity: 0.9;">คัดลอก</div>
      <div style="font-size: 32px; font-weight: 700; margin-top: 8px;">{{ $platformTotals['copy'] }}</div>
    </div>
  </div>

  <div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
      <thead>
        <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
          <th style="padding: 12px; text-align: left;">บทความ</th>
          <th style="padding: 12px; text-align: center;">Facebook</th>
          <th style="padding: 12px; text-align: center;">X/Twitter</th>
          <th style="padding: 12px; text-align: center;">LINE</th>
          <th style="padding: 12px; text-align: center;">WhatsApp</th>
          <th style="padding: 12px; text-align: center;">คัดลอก</th>
          <th style="padding: 12px; text-align: center;">รวม</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($articles as $article)
          @php
            $shares = [];
            foreach ($article->shares as $share) {
              $shares[$share->platform] = $share->count ?? 0;
            }
            $articleTotal = array_sum($shares);
          @endphp

          @if ($articleTotal > 0)
            <tr style="border-bottom: 1px solid #eee;">
              <td style="padding: 12px;">
                <a href="{{ route('articles.show', $article->slug) }}" target="_blank"
                   style="color: #1877f2; text-decoration: none; font-weight: 500;">
                  {{ $article->title }}
                </a>
                <div style="font-size: 12px; color: #666; margin-top: 4px;">
                  เผยแพร่ {{ optional($article->published_at)->format('d/m/Y H:i') ?: 'ยังไม่เผยแพร่' }}
                </div>
              </td>
              <td style="padding: 12px; text-align: center; font-weight: 600;">{{ $shares['facebook'] ?? 0 }}</td>
              <td style="padding: 12px; text-align: center; font-weight: 600;">{{ $shares['twitter'] ?? 0 }}</td>
              <td style="padding: 12px; text-align: center; font-weight: 600;">{{ $shares['line'] ?? 0 }}</td>
              <td style="padding: 12px; text-align: center; font-weight: 600;">{{ $shares['whatsapp'] ?? 0 }}</td>
              <td style="padding: 12px; text-align: center; font-weight: 600;">{{ $shares['copy'] ?? 0 }}</td>
              <td style="padding: 12px; text-align: center; font-weight: 700; background: #f9f9f9;">{{ $articleTotal }}</td>
            </tr>
          @endif
        @empty
          <tr>
            <td colspan="7" style="padding: 20px; text-align: center; color: #999;">
              ยังไม่มีการแชร์บทความ
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<style>
  table a:hover {
    text-decoration: underline;
  }
</style>
@endsection
