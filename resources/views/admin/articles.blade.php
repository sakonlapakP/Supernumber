@extends('layouts.admin')

@section('content')
    @php
       $thaiMonths = [
           1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
           5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
           9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
       ];
       
       $totalPlanned = $articlePlans->count();
       $totalDone = 0;
       
       $checkItemDone = function($item) use ($existingArticlesInfo) {
           if (\Carbon\Carbon::parse($item->publish_date)->isPast()) {
               return true;
           }
           foreach ($existingArticlesInfo as $article) {
               if ($article['date'] === $item->publish_date->format('Y-m-d')) {
                   return true;
               }
               if ($item->is_lottery) {
                   $dateObj = $item->publish_date;
                   $isRound1 = (int)$dateObj->format('j') <= 15;
                   $pattern = '/^thai-goverment-lottery-' . $dateObj->format('Ym') . ($isRound1 ? 'first' : 'second') . '$/';
                   if (preg_match($pattern, (string)$article['slug'])) {
                       return true;
                   }
               }
           }
           return false;
       };

       foreach($articlePlans as $item) {
           if ($checkItemDone($item)) $totalDone++;
       }

       $groupedPlans = $articlePlans->groupBy(function($plan) use ($thaiMonths) {
           $date = \Carbon\Carbon::parse($plan->publish_date);
           return $thaiMonths[$date->month] . ' ' . ($date->year + 543 - 2500); 
       });

       $isManager = session('admin_user_role') === \App\Models\User::ROLE_MANAGER;
    @endphp
    <div class="admin-page-head">
      <div>
        <h1>บทความ</h1>
      </div>
      <div class="admin-page-actions article-admin-actions">
        @if(in_array(session('admin_user_role'), [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER]))
          <button type="button" class="admin-button admin-button--ai-top" onclick="openAiPromptModal()" title="AI Prompt">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.456-2.455l.259-1.036.259 1.036a3.375 3.375 0 002.455 2.456l1.036.259-1.036.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
            </svg>
            <span>Generate prompt AI</span>
          </button>
          <button type="button" class="admin-button admin-button--import-top" onclick="openImportModal()" title="นำเข้า JSON">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
            </svg>
            <span>นำเข้า</span>
          </button>
        @endif
        <a href="{{ route('admin.articles.create') }}" class="admin-button article-admin-actions__create">เพิ่มบทความ</a>
      </div>
    </div>

    @if (session('status_message'))
      <div class="admin-alert admin-alert--success">
        {{ session('status_message') }}
      </div>
    @endif

    @if(!config('services.line.group_id') && !config('services.line.groups.lottery'))
      <div style="background: #fff4f4; border: 1px solid #ffcccc; color: #cc0000; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; display: flex; align-items: center; gap: 10px;">
        <span>⚠️ ยังไม่ได้ตั้งค่า LINE Group ID! ปุ่มส่ง LINE จะใช้งานไม่ได้จนกว่าจะตั้งค่าในเมนู <a href="{{ route('admin.line-settings') }}" style="color: #1877F2; text-decoration: underline;">ตั้งค่า LINE</a></span>
      </div>
    @endif

<style>
  /* Desktop styles for bottom actions */
  .article-bottom-actions {
    display: none;
  }

  /* Desktop styles for top buttons */
  .admin-button--ai-top {
    background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important;
    color: white !important;
    border: none !important;
    display: flex !important;
    align-items: center;
    gap: 8px;
    padding: 10px 16px !important;
    border-radius: 12px !important;
    font-weight: 600 !important;
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
    transition: all 0.2s;
    cursor: pointer;
    text-decoration: none;
    font-family: 'Kanit', sans-serif;
  }
  .admin-button--ai-top:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 15px rgba(124, 58, 237, 0.3);
    opacity: 0.9;
  }
  
  .admin-button--import-top {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border: 1px solid #e2e8f0 !important;
    display: flex !important;
    align-items: center;
    gap: 8px;
    padding: 10px 16px !important;
    border-radius: 12px !important;
    font-weight: 600 !important;
    transition: all 0.2s;
    cursor: pointer;
    text-decoration: none;
    font-family: 'Kanit', sans-serif;
  }
  .admin-button--import-top:hover {
    background: #e2e8f0 !important;
    color: #1e293b !important;
  }


  .plan-action-btn {
    background: #f1f5f9;
    border: none;
    padding: 10px;
    border-radius: 12px;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    min-height: 40px;
    -webkit-tap-highlight-color: transparent;
  }
  @media (hover: hover) {
    .plan-action-btn:hover {
      background: #e2e8f0;
      color: #1e293b;
    }
    .plan-action-btn--delete:hover {
      background: #fef2f2;
      color: #dc2626;
    }
  }
  .plan-action-btn:active {
    transform: scale(0.95);
  }

  @media (min-width: 768px) {
    .article-admin-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .admin-action-cell .admin-action-group {
      display: flex !important;
      gap: 8px;
      width: auto;
    }
    
    .admin-action-cell .admin-button {
      border-radius: 10px !important;
      padding: 7px 14px !important;
      font-size: 13px !important;
      font-weight: 700 !important;
      display: inline-flex !important;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
      border: 1px solid transparent !important;
    }
    
    .article-action-preview, .article-action-edit {
      background: #f1f5f9 !important;
      color: #334155 !important;
      border-color: #e2e8f0 !important;
    }
    
    .article-action-preview:hover, .article-action-edit:hover {
      background: #e2e8f0 !important;
      transform: translateY(-1px);
    }
    
    .article-action-share {
      box-shadow: 0 4px 10px rgba(24, 119, 242, 0.2);
    }
    .article-action-share:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 14px rgba(24, 119, 242, 0.3);
      opacity: 0.9;
    }
    .article-action-share:disabled,
    .article-action-share.is-disabled,
    .article-action-share.is-disabled:hover {
      background: #f1f5f9 !important;
      color: #94a3b8 !important;
      border-color: #e2e8f0 !important;
      box-shadow: none !important;
      cursor: not-allowed !important;
      opacity: 1 !important;
      transform: none !important;
    }
    
    .article-action-delete {
      box-shadow: 0 4px 10px rgba(220, 38, 38, 0.15);
    }
    .article-action-delete:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 14px rgba(220, 38, 38, 0.25);
      opacity: 0.9;
    }
  }

  @media (max-width: 767px) {
    .admin-button--ai-top,
    .admin-button--import-top {
      display: none !important;
    }
    .admin-page-head {
      grid-template-columns: minmax(0, 1fr) auto;
      align-items: center;
      gap: 10px;
    }
    .admin-page-head h1 {
      font-size: 30px;
    }
    .article-admin-actions {
      display: grid;
      grid-template-columns: auto auto;
      width: auto;
      gap: 4px;
      justify-content: end;
    }
    .article-admin-actions form,
    .article-admin-actions .admin-button {
      width: 100%;
    }
    .article-admin-actions__import,
    .article-admin-actions__create {
      min-height: 48px;
      padding: 10px 12px;
      border-radius: 16px;
      font-size: 14px;
      line-height: 1.25;
      white-space: normal;
      text-align: center;
    }
    .article-admin-actions__import {
      width: 52px !important;
      min-width: 52px;
      padding-left: 10px;
      padding-right: 10px;
    }
    .article-admin-actions__create {
      min-width: 118px;
    }
    .article-admin-card {
      border-radius: 22px;
      overflow: hidden;
    }
    .article-admin-toolbar {
      padding: 14px;
      display: grid !important;
      grid-template-columns: 1fr;
      gap: 10px;
      align-items: stretch;
      border-bottom: 1px solid #e6edf8;
    }
    .article-admin-toolbar .admin-input {
      max-width: none !important;
      min-height: 46px;
      border-radius: 16px;
      font-size: 14px;
    }
    .article-admin-toolbar__count {
      justify-self: start;
      padding: 6px 10px;
      border-radius: 999px;
      background: #f4f7fb;
    }
    .admin-table-wrap {
      overflow: visible;
    }
    .admin-table {
      min-width: 100% !important;
      background: transparent;
      border: none;
    }
    .admin-table thead {
      display: none;
    }
    .admin-table tbody {
      display: grid;
      gap: 8px;
      padding: 0;
    }
    .admin-table tr {
      display: grid;
      grid-template-columns: 46px minmax(0, 1fr);
      column-gap: 8px;
      align-items: start;
      background: #ffffff;
      border-radius: 14px;
      box-shadow: 0 10px 24px rgba(30, 45, 69, 0.06);
      box-shadow: 0 4px 12px rgba(30, 45, 69, 0.08);
      padding: 8px 8px;
      border: 1px solid #dce6f3;
    }
    .admin-table td {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      padding: 5px 0;
      border-bottom: 1px solid #f1f5f9;
      text-align: right;
    }
    .admin-table td:first-child {
      grid-row: 1 / 4;
      justify-content: flex-start;
      border-bottom: 0;
      padding-top: 0;
      padding-bottom: 0;
      display: grid;
      justify-items: center;
      align-content: start;
      gap: 0;
    }
    .admin-table td:first-child > div {
      width: 46px !important;
      height: 46px !important;
      border-radius: 10px !important;
    }
    .article-mobile-meta {
      display: inline-flex !important;
      align-items: center;
      justify-content: flex-start;
      gap: 6px;
      width: auto;
      text-align: center;
      margin-top: 0;
    }
    .article-mobile-meta .admin-status-pill {
      width: 10px;
      height: 10px;
      min-width: 10px;
      min-height: 10px;
      padding: 0;
      border-radius: 999px;
      overflow: hidden;
      color: transparent !important;
      font-size: 0;
      line-height: 0;
    }
    .article-mobile-meta__date {
      color: #7488a8;
      font-size: 8px;
      font-weight: 700;
      line-height: 1.2;
      word-break: keep-all;
    }
    .admin-table td:nth-child(2) {
      display: block;
      text-align: left;
      border-bottom: 1px solid #edf2f8;
      padding-top: 0;
      padding-bottom: 3px;
      margin-bottom: 0;
      min-width: 0;
    }
    .admin-table td:nth-child(2) div:first-child {
      font-size: 13px;
      line-height: 1.25;
      margin-bottom: 0 !important;
      text-align: left;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .admin-table td:nth-child(2) .admin-muted {
      display: none;
    }
    .admin-table td::before {
      content: attr(data-label);
      font-weight: 700;
      color: #94a3b8;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      text-align: left;
      flex: 0 0 auto;
    }
    .admin-table td:first-child::before,
    .admin-table td:nth-child(2)::before,
    .admin-table td:nth-child(3)::before {
      display: none;
    }
    .admin-table td:nth-child(3) {
      display: none;
    }
    .admin-table td:last-child {
      border-bottom: none;
      display: grid;
      grid-template-columns: 50px minmax(0, 1fr);
      column-gap: 6px;
      align-items: center;
      padding-bottom: 0;
      text-align: left;
    }
    .admin-table td:last-child::before {
      display: none;
    }
    .admin-action-group {
      display: grid !important;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 5px;
      width: 100%;
    }
    .admin-action-group:has(button[id^="btn-share-social"]),
    .admin-action-group:has(button[title="แชร์ไป Facebook Page"]) {
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .admin-action-group .admin-button:disabled,
    .admin-action-group .admin-button.is-disabled {
      color: #94a3b8 !important;
      background: rgba(148, 163, 184, 0.1) !important;
      cursor: not-allowed;
    }
    .admin-action-group .admin-button:disabled svg,
    .admin-action-group .admin-button.is-disabled svg {
      opacity: 0.5;
    }
    .admin-action-group:has(button[id^="btn-share-social"]) .admin-button,
    .admin-action-group:has(button[title="แชร์ไป Facebook Page"]) .admin-button {
      font-size: 12px;
      padding-left: 4px;
      padding-right: 4px;
    }
    .admin-action-group > *,
    .admin-action-group form,
    .admin-action-group .admin-button {
      width: 100% !important;
      margin: 0 !important;
    }
    .admin-action-group .admin-button {
      min-height: 31px;
      border-radius: 9px;
      font-size: 12px;
      line-height: 1.2;
      text-align: center;
      padding: 5px 4px;
    }
    .admin-action-group form:has(button[title="ลบบทความ"]) {
      grid-column: span 1;
    }
    .admin-status-pill {
      white-space: nowrap;
    }
    .admin-page-head {
      margin: 0 -16px 0;
      padding: 18px 16px;
      background: #ffffff;
      border-bottom: 1px solid #dbe5f2;
    }
    .admin-page-head h1 {
      font-size: 0;
      line-height: 1;
      display: inline-flex;
      align-items: center;
      gap: 12px;
    }
    .admin-page-head h1::before {
      content: "S";
      width: 30px;
      height: 30px;
      border-radius: 9px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #1d1816 0%, #46372b 100%);
      border: 1px solid rgba(216, 163, 74, 0.5);
      color: #d8a34a;
      font-family: "Cinzel", serif;
      font-size: 19px;
      font-weight: 700;
    }
    .admin-page-head h1::after {
      content: "ARTICLES";
      color: #111827;
      font-size: 20px;
      font-weight: 800;
      letter-spacing: 0;
    }
    .article-admin-actions__create {
      display: none;
    }
    .article-admin-actions__import {
      width: 44px !important;
      min-width: 44px;
      min-height: 44px;
      border-radius: 14px;
      background: transparent !important;
      border-color: transparent !important;
      color: #7c3aed !important;
      font-size: 24px;
      font-weight: 800;
      padding: 0;
    }
    .admin-card.article-admin-card {
      margin: 0 -16px;
      border: 0;
      border-radius: 0;
      box-shadow: none;
      background: #eef4fb;
    }
    .article-admin-toolbar {
      display: none !important;
    }
    .admin-table tbody {
      display: flex !important;
      flex-direction: column;
      gap: 36px;
      padding: 14px 16px 160px;
    }
    .admin-table tr.article-row {
      display: block;
      padding: 20px;
      margin: 0 4px;
      border-radius: 24px;
      box-shadow: 0 10px 30px rgba(30, 45, 69, 0.05);
      border: 1px solid #e2eaf5;
      background: #ffffff;
    }
    .admin-table td:first-child,
    .admin-table td:nth-child(3) {
      display: none;
    }
    .admin-table td:nth-child(2) {
      border-bottom: 0;
      padding: 0;
    }
    .admin-table td:nth-child(2) div:first-child {
      font-size: 20px;
      line-height: 1.25;
      font-weight: 800 !important;
      color: #1e2d45 !important;
    }
    .article-mobile-excerpt {
      display: block !important;
      margin-top: 8px;
      color: #7488a8;
      font-size: 14px;
      font-weight: 600;
      line-height: 1.35;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .admin-table td:last-child {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 18px;
      padding: 0;
    }
    .article-mobile-meta {
      flex: 1 1 auto;
      min-width: 0;
      gap: 10px;
    }
    .article-mobile-meta .admin-status-pill {
      width: auto;
      height: auto;
      min-width: 0;
      min-height: 0;
      padding: 4px 12px;
      border-radius: 999px;
      color: #a86d17 !important;
      font-size: 13px;
      line-height: 1.2;
      background: #fffaf0;
      border-color: #f3d894;
    }
    .article-mobile-meta .admin-status-pill--active {
      color: #16876d !important;
      background: #effbf6;
      border-color: #c7eadf;
    }
    .article-mobile-meta__date {
      font-size: 13px;
      color: #7488a8;
    }
    .admin-action-group {
      flex: 0 0 auto;
      display: inline-grid !important;
      grid-template-columns: repeat(4, 32px) !important;
      gap: 10px;
      width: auto;
    }
    .admin-action-group .admin-button {
      min-height: 32px;
      width: 32px !important;
      padding: 0;
      border: 0;
      background: transparent !important;
      color: #223a63 !important;
      font-size: 0;
      border-radius: 8px;
    }
    .admin-action-group form {
      width: 32px !important;
    }
    .article-action-preview svg,
    .article-action-edit svg,
    .article-action-share svg,
    .article-action-delete svg {
      width: 18px;
      height: 18px;
    }
    .article-action-delete {
      color: #c54b3d !important;
    }
    .article-bottom-actions {
      display: flex;
      position: fixed;
      right: 16px;
      bottom: 18px;
      z-index: 100;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .article-mobile-fab {
      min-height: 56px;
      padding: 0 20px;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: #223a63;
      color: #ffffff;
      text-decoration: none;
      box-shadow: 0 10px 25px rgba(34, 58, 99, 0.3);
      font-size: 16px;
      font-weight: 700;
      transition: transform 0.2s;
    }
    .article-mobile-fab:active {
      transform: scale(0.95);
    }
    .article-mobile-fab::before {
      content: "+";
      font-size: 30px;
      line-height: 1;
      font-weight: 300;
    }
    .article-import-fab {
      width: 56px;
      height: 56px;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #7c3aed;
      color: #ffffff;
      border: none;
      box-shadow: 0 10px 25px rgba(124, 58, 237, 0.3);
      cursor: pointer;
      transition: transform 0.2s;
    }
    .article-import-fab:active {
      transform: scale(0.95);
    }
    .article-import-fab svg {
      width: 24px;
      height: 24px;
      opacity: 0.9;
    }
  }

  @media (max-width: 430px) {
    .admin-table tr {
      grid-template-columns: 44px minmax(0, 1fr);
      column-gap: 7px;
    }
    .admin-table td:first-child > div {
      width: 44px !important;
      height: 44px !important;
    }
    .admin-table td:nth-child(2) div:first-child {
      font-size: 13px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .admin-table td {
      gap: 6px;
      padding: 3px 0;
    }
    .admin-table td:last-child {
      display: grid;
      grid-template-columns: 48px minmax(0, 1fr);
      column-gap: 5px;
    }
    .admin-action-group {
      gap: 5px;
    }
    .admin-action-group .admin-button {
      min-height: 31px;
      font-size: 12px;
      padding: 5px 4px;
    }
  }

  @media (max-width: 360px) {
    .article-admin-actions {
      grid-template-columns: 1fr;
    }
    .admin-action-group {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .admin-table td:last-child {
      display: block;
    }
    .admin-table td:last-child::before {
      margin-bottom: 10px;
    }
  }

  @media (max-width: 767px) {
    .admin-table,
    .admin-table tbody {
      display: block;
      width: 100%;
      max-width: 100%;
    }
    .admin-page-head {
      margin: 0 -16px;
      padding: 10px 16px 0;
      border-bottom: 0;
      background: #eef4fb;
      display: flex;
      justify-content: flex-end;
      align-items: center;
    }
    .admin-page-head h1 {
      display: none;
    }
    .article-admin-actions {
      display: flex;
      justify-content: flex-end;
      width: 100%;
    }
    .article-admin-actions__import {
      width: 44px !important;
      min-width: 44px;
      min-height: 44px;
      border-radius: 14px;
      background: transparent !important;
      border-color: transparent !important;
      color: #7c3aed !important;
      font-size: 24px;
      font-weight: 800;
      padding: 0;
    }
    .article-admin-actions__create {
      display: none;
    }
    .admin-table tr.article-row {
      display: block;
      width: 100%;
      max-width: 100%;
      padding: 16px;
      border-radius: 24px;
    }
    .admin-table td {
      width: 100%;
      max-width: 100%;
    }
    .admin-table td:first-child,
    .admin-table td:nth-child(3) {
      display: none;
    }
    .admin-table td:nth-child(2) {
      display: block;
      border-bottom: 0;
      padding: 0;
      width: 100%;
      max-width: 100%;
      overflow: hidden;
    }
    .admin-table td:nth-child(2) div:first-child {
      display: block;
      max-width: 100%;
      font-size: 20px;
      line-height: 1.25;
    }
    .admin-table td:last-child {
      display: flex;
      align-items: center;
      gap: 8px;
      justify-content: space-between;
      margin-top: 18px;
      padding: 0;
    }
    .article-mobile-meta {
      flex: 0 1 auto;
      min-width: 0;
    }
    .admin-action-group,
    .admin-action-group:has(button[id^="btn-share-social"]),
    .admin-action-group:has(button[title="แชร์ไป Facebook Page"]) {
      flex: 0 0 auto;
      margin-left: auto !important;
      display: inline-grid !important;
      grid-template-columns: repeat(4, 28px) !important;
      gap: 8px;
      width: auto;
    }
    .admin-action-group .admin-button {
      min-height: 32px;
      width: 32px !important;
      padding: 0;
      font-size: 0 !important;
      color: #223a63 !important;
      background: rgba(34, 58, 99, 0.08) !important;
      border: 0 !important;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .admin-action-group .article-action-delete {
      color: #dc2626 !important;
      background: rgba(220, 38, 38, 0.08) !important;
    }
    .admin-action-group .article-action-share {
      color: #1877F2 !important;
      background: rgba(24, 119, 242, 0.08) !important;
    }
    .admin-action-group .article-action-share:disabled,
    .admin-action-group .article-action-share.is-disabled {
      color: #94a3b8 !important;
      background: rgba(148, 163, 184, 0.12) !important;
    }
    .admin-action-group .admin-button svg {
      display: block !important;
      width: 18px;
      height: 18px;
    }
    .article-action-preview svg,
    .article-action-edit svg,
    .article-action-share svg,
    .article-action-delete svg {
      display: block;
    }
    .article-mobile-meta__date {
      white-space: nowrap;
    }
  }
</style>

    <div class="admin-card article-admin-card">
      <div class="article-admin-toolbar" style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <form action="{{ route('admin.articles') }}" method="GET" style="display: flex; gap: 12px; align-items: center; flex: 1; max-width: 600px;">
          <input type="text" id="article-search" placeholder="ค้นหาหัวข้อบทความ..." class="admin-input" style="flex: 1;">
          <select id="plan-month-filter" name="month_plan" class="admin-input" style="width: 180px; background-color: #fff; cursor: pointer; border-color: #e2e8f0; font-weight: 600; color: #1e293b;" onchange="this.form.submit()">
            <option value="">ทั้งหมด (แผนงาน)</option>
            @foreach($groupedPlans->keys() as $monthName)
              <option value="{{ $monthName }}" {{ request('month_plan') === $monthName ? 'selected' : '' }}>{{ $monthName }}</option>
            @endforeach
          </select>
        </form>
        <div class="admin-muted article-admin-toolbar__count" style="font-size: 13px; font-weight: 600;">ทั้งหมด {{ number_format($articles->total()) }} รายการ</div>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
        <thead>
          <tr>
            <th>รูปหน้าปก</th>
            <th>หัวข้อ</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($articles as $article)
            @php
              $isLotteryArticle = preg_match('/^thai-goverment-lottery-(\d{4})(\d{2})(first|second)$/', (string)$article->slug, $matches) === 1;
              $lotteryIsComplete = true;
              $publishStatusLabel = 'Draft';
              $publishStatusClass = 'admin-status-pill--hold';
              $publishStatusStyle = '';
              if ($article->is_published) {
                  $isScheduled = $article->published_at && $article->published_at->gt(now('Asia/Bangkok'));
                  $publishStatusLabel = $isScheduled ? 'ตั้งเวลาเผยแพร่' : 'Published';
                  $publishStatusClass = $isScheduled ? 'admin-status-pill--hold' : 'admin-status-pill--active';
                  $publishStatusStyle = $isScheduled ? 'background: #eef6ff; color: #2563eb; border-color: #bfdbfe;' : '';
              }
              if ($isLotteryArticle) {
                  $year = $matches[1]; $month = $matches[2]; $round = $matches[3];
                  $lotteryResult = \App\Models\LotteryResult::whereYear('draw_date', $year)->whereMonth('draw_date', $month)->get()->first(function($r) use ($round) {
                      $d = $r->source_draw_date ?? $r->draw_date;
                      return $round === 'first' ? (int)$d->format('j') <= 15 : (int)$d->format('j') > 15;
                  });
                  $lotteryIsComplete = $lotteryResult ? $lotteryResult->is_complete : false;
              }
            @endphp
            @php
              $thaiMonthsFull = [
                  1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                  5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                  9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
              ];
              $rowMonthYear = '';
              if ($article->published_at) {
                  $dt = $article->published_at->timezone('Asia/Bangkok');
                  $rowMonthYear = $thaiMonthsFull[(int)$dt->format('n')] . ' ' . (($dt->format('Y') + 543) % 100);
              }
            @endphp
            <tr class="article-row" data-title="{{ strtolower($article->title) }}" data-slug="{{ strtolower($article->slug) }}" data-month-year="{{ $rowMonthYear }}">
              <td data-label="รูปหน้าปก">
                <div style="width: 60px; height: 60px; background: #f1f5f9; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
                  @php
                    $thumbPath = $article->cover_image_path ?: ($article->cover_image_square_path ?: $article->cover_image_landscape_path);
                  @endphp
                  @if($thumbPath)
                    <img src="{{ Storage::disk('public')->url($thumbPath) }}" style="width: 100%; height: 100%; object-fit: cover;">
                  @else
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8;">🖼️</div>
                  @endif
                </div>
              </td>
              <td data-label="หัวข้อ">
                <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px;">{{ $article->title }}</div>
                <div class="admin-muted" style="font-size: 12px;">{{ $article->slug }}</div>
                <div class="article-mobile-excerpt" style="display: none;">{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->sanitizedContent()), 120) }}</div>
              </td>
              <td data-label="สถานะ">
                <span class="admin-status-pill {{ $publishStatusClass }}" @if($publishStatusStyle) style="{{ $publishStatusStyle }}" @endif>{{ $publishStatusLabel }}</span>
                
                @if($isLotteryArticle && !$lotteryIsComplete)
                  <div style="margin-top: 4px;">
                    <span class="admin-status-pill admin-status-pill--hold" style="font-size: 10px; background: #fff7ed; color: #c2410c; border-color: #fdba74;">⏳ รอผลครบ 100%</span>
                  </div>
                @endif

                <div class="admin-muted" style="font-size: 12px; margin-top: 6px;">
                  {{ $article->published_at ? $article->published_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') : '-' }}
                </div>
              </td>
              <td data-label="จัดการ" class="admin-action-cell">
                <div class="article-mobile-meta" style="display: none;">
                  <span class="admin-status-pill {{ $publishStatusClass }}" aria-label="{{ $publishStatusLabel }}" title="{{ $publishStatusLabel }}" @if($publishStatusStyle) style="{{ $publishStatusStyle }}" @endif>{{ $publishStatusLabel }}</span>
                  @if($isLotteryArticle && !$lotteryIsComplete)
                    <span class="admin-status-pill admin-status-pill--hold" style="font-size: 10px; background: #fff7ed; color: #c2410c; border-color: #fdba74;">รอผลครบ</span>
                  @endif
                  <span class="article-mobile-meta__date">{{ $article->published_at ? $article->published_at->timezone('Asia/Bangkok')->format('d M Y | H:i') : '-' }}</span>
                </div>
                <div class="admin-action-group">
                  @php
                    $isPreview = !$article->is_published || ($article->published_at && $article->published_at->gt(now('Asia/Bangkok')));
                    $viewUrl = $isPreview 
                        ? URL::temporarySignedRoute('articles.signed-preview', now()->addHours(24), ['article' => $article])
                        : route('articles.show', $article->slug);
                  @endphp
                  <a href="{{ $viewUrl }}" target="_blank" class="admin-button admin-button--muted admin-button--compact article-action-preview" title="{{ $isPreview ? 'ดูตัวอย่าง' : 'ดูบนเว็บไซต์' }}" aria-label="ดูตัวอย่าง">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.31 5 12 5s8.601 3.049 9.964 6.678c.07.234.07.468 0 .702-1.364 3.629-5.275 6.678-9.964 6.678s-8.601-3.049-9.964-6.678z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    ดู
                  </a>
                  <a href="{{ route('admin.articles.edit', $article) }}" class="admin-button admin-button--muted admin-button--compact article-action-edit" title="แก้ไข" aria-label="แก้ไข">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                    แก้ไข
                  </a>
                  @if($article->is_published && $isLotteryArticle)
                    <form id="share-social-form-{{ $article->id }}" action="{{ route('admin.articles.share-social', $article) }}" method="POST" style="display: inline;">
                      @csrf
                      <input type="hidden" name="manual_image_url" id="share-social-image-{{ $article->id }}">
                      <button type="button" 
                              id="btn-share-social-{{ $article->id }}"
                              onclick="renderAndShareSocial(this, '{{ $article->id }}', '{{ $article->cover_image_square_path }}', '{{ route('admin.articles.upload-rendered-image', $article) }}', '{{ route('admin.articles.report-render-error', $article) }}', {{ $lotteryIsComplete ? 1 : 0 }})"
                              class="admin-button admin-button--compact article-action-share" 
                              style="background: #1877F2; color: #fff; border-color: #1877F2; {{ !$lotteryIsComplete ? 'opacity: 0.6; cursor: not-allowed;' : '' }}" 
                              title="{{ !$lotteryIsComplete ? 'รอให้หวยออกครบ 100% ก่อนถึงจะแชร์ได้' : 'แปลงรูปเป็น PNG แล้วแชร์ไป Facebook Page' }}"
                              aria-label="แชร์">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185zm3.933 12.814a2.25 2.25 0 10-3.933-2.185 2.25 2.25 0 003.933 2.185z" /></svg>
                        แชร์
                      </button>
                    </form>
                  @elseif($article->is_published)
                    <form action="{{ route('admin.articles.share-social', $article) }}" method="POST" style="display: inline;" onsubmit="return confirm('ยืนยันแชร์บทความนี้ไปที่ Facebook Page?')">
                      @csrf
                      <input type="hidden" name="manual_image_url" value="{{ $article->cover_image_landscape_path ?: ($article->cover_image_path ?: $article->cover_image_square_path) }}">
                      <button type="submit"
                              class="admin-button admin-button--compact article-action-share"
                              style="background: #1877F2; color: #fff; border-color: #1877F2;"
                              title="แชร์ไป Facebook Page"
                              aria-label="แชร์">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185zm3.933 12.814a2.25 2.25 0 10-3.933-2.185 2.25 2.25 0 003.933 2.185z" /></svg>
                        แชร์
                      </button>
                    </form>
                  @else
                    <button type="button"
                            class="admin-button admin-button--compact article-action-share is-disabled"
                            style="background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; cursor: not-allowed;"
                            title="กรุณาเผยแพร่บทความก่อนจึงจะแชร์ได้"
                            disabled>
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185zm3.933 12.814a2.25 2.25 0 10-3.933-2.185 2.25 2.25 0 003.933 2.185z" /></svg>
                      แชร์
                    </button>
                  @endif
                  @if(in_array(session('admin_user_role'), [\App\Models\User::ROLE_MANAGER, \App\Models\User::ROLE_ADMIN], true))
                    <form action="{{ route('admin.articles.delete', $article) }}" method="POST" style="display: inline;" onsubmit="return confirm('ยืนยันลบบทความนี้? การลบจะลบไฟล์รูปและคอมเมนต์ที่เกี่ยวข้องด้วย')">
                      @csrf
                      @method('DELETE')
                      <button
                        type="submit"
                        class="admin-button admin-button--compact article-action-delete"
                        style="background: #dc2626; color: #fff; border-color: #dc2626;"
                        title="ลบบทความ"
                        aria-label="ลบบทความ"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                        ลบ
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="admin-muted" style="text-align: center; padding: 40px;">ไม่พบรายการบทความ</td>
            </tr>
          @endforelse
          <tr id="articles-empty-row" style="display: none;">
            <td colspan="4" class="admin-muted" style="text-align: center; padding: 40px;">ไม่พบผลลัพธ์การค้นหา</td>
          </tr>
        </tbody>
        </table>
      </div>
    </div>

    @if ($articles->hasPages())
      <nav class="admin-pagination">
        @if ($articles->onFirstPage())
          <span>ก่อนหน้า</span>
        @else
          <a href="{{ $articles->previousPageUrl() }}">ก่อนหน้า</a>
        @endif

        @php
          $startPage = max(1, $articles->currentPage() - 2);
          $endPage = min($articles->lastPage(), $articles->currentPage() + 2);
        @endphp

        @for ($page = $startPage; $page <= $endPage; $page++)
          @if ($page === $articles->currentPage())
            <span class="is-active">{{ $page }}</span>
          @else
            <a href="{{ $articles->url($page) }}">{{ $page }}</a>
          @endif
        @endfor

        @if ($articles->hasMorePages())
          <a href="{{ $articles->nextPageUrl() }}">ถัดไป</a>
        @else
          <span>ถัดไป</span>
        @endif
      </nav>
    @endif

    <div class="article-bottom-actions">
      @if(in_array(session('admin_user_role'), [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER]))
        <button type="button" class="article-ai-fab" onclick="openAiPromptModal()" title="AI Prompt" style="width: 56px; height: 56px; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: #ffffff; border: none; box-shadow: 0 10px 25px rgba(124, 58, 237, 0.3); cursor: pointer; transition: transform 0.2s; margin-right: 8px;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 28px; height: 28px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.456-2.455l.259-1.036.259 1.036a3.375 3.375 0 002.455 2.456l1.036.259-1.036.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
          </svg>
        </button>
        <button type="button" class="article-import-fab" onclick="openImportModal()" title="นำเข้า JSON">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
          </svg>
        </button>
      @endif
      <a href="{{ route('admin.articles.create') }}" class="article-mobile-fab">เขียนบทความ</a>
    </div>

    <style>
      .content-plan-card { margin-top: 40px; border-top: 2px solid #7c3aed; padding: 30px; }
      .plan-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
      .plan-title { margin: 0; font-size: 20px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 10px; }
      .plan-status-summary { font-size: 14px; font-weight: 700; background: #f1f5f9; padding: 6px 14px; border-radius: 99px; color: #475569; }
      .plan-table-wrap { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
      .plan-table { width: 100%; border-collapse: collapse; font-family: 'Kanit', sans-serif; }
      .plan-table th { background: #f8fafc; text-align: left; padding: 14px 18px; font-size: 13px; font-weight: 700; color: #64748b; border-bottom: 2px solid #f1f5f9; }
      .plan-table td { padding: 14px 18px; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
      .plan-table tr:last-child td { border-bottom: none; }
      .plan-table tr:hover td { background: #fcfdff; }
      .plan-row--checked { background: #f0fdf4 !important; }
      .plan-row--checked td { color: #065f46; }
      .plan-row--lottery { background: #fffbeb !important; }
      .plan-row--lottery td { color: #854d0e; }
      .month-divider { background: #f1f5f9; font-weight: 800; color: #475569; padding: 10px 18px; font-size: 13px; }
      .check-icon { color: #10b981; font-weight: 900; margin-right: 8px; font-size: 16px; }
      .type-pill { display: inline-block; padding: 2px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
      .type-pill--หวย { background: #fef2f2; color: #ef4444; }
      .type-pill--วันสำคัญ { background: #eff6ff; color: #3b82f6; }
      .type-pill--วันมู { background: #faf5ff; color: #9333ea; }
      .type-pill--evergreen { background: #f0fdf4; color: #16a34a; }
      @media (max-width: 768px) {
        .plan-table th:nth-child(1), .plan-table td:nth-child(1) { display: none; }
      }
    </style>

    @php
      $planData = [
          ['month' => 'พฤษภาคม 69', 'items' => [
              ['d' => 11, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันพืชมงคล: เลขมงคลการเงินและความมั่งคั่ง', 'date' => '2026-05-11'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย (สถิติ/วิเคราะห์)', 'date' => '2026-05-16', 'is_lottery' => true],
              ['d' => 22, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(Pillar Content เติมช่องว่างปลายเดือน)', 'date' => '2026-05-22'],
              ['d' => 27, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(Pillar Content เลี้ยงกระแสก่อยวันพระใหญ่)', 'date' => '2026-05-27'],
              ['d' => 31, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันวิสาขบูชา: เลขสติปัญญาและการเริ่มต้นใหม่', 'date' => '2026-05-31'],
          ]],
          ['month' => 'มิถุนายน 69', 'items' => [
              ['d' => 1, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-06-01', 'is_lottery' => true],
              ['d' => 8, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'date' => '2026-06-08'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-06-16', 'is_lottery' => true],
              ['d' => 21, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน)', 'date' => '2026-06-21'],
              ['d' => 26, 't' => '09:00', 'type' => 'วันมู', 'topic' => 'วันสุนทรภู่: เลขมงคลสายวาทศิลป์และการเจรจา', 'date' => '2026-06-26'],
          ]],
          ['month' => 'กรกฎาคม 69', 'items' => [
              ['d' => 1, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-07-01', 'is_lottery' => true],
              ['d' => 8, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'date' => '2026-07-08'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-07-16', 'is_lottery' => true],
              ['d' => 23, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน)', 'date' => '2026-07-23'],
              ['d' => 28, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'คอนเทนต์มงคลรวมใจ (ร.10)', 'date' => '2026-07-28'],
              ['d' => 29, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันอาสาฬหบูชา: ปรับพลังงานตัวเลข', 'date' => '2026-07-29'],
          ]],
          ['month' => 'สิงหาคม 69', 'items' => [
              ['d' => 1, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-08-01', 'is_lottery' => true],
              ['d' => 8, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'date' => '2026-08-08'],
              ['d' => 12, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันแม่: เลขมงคลสุขภาพ', 'date' => '2026-08-12'],
              ['d' => 15, 't' => '09:00', 'type' => 'วันมู', 'topic' => 'วันคเณศจตุรถี: เลขมงคลประทานพร', 'date' => '2026-08-15'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-08-16', 'is_lottery' => true],
              ['d' => 25, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน เพราะวันสำคัญกระจุกต้นเดือน)', 'date' => '2026-08-25'],
          ]],
          ['month' => 'กันยายน 69', 'items' => [
              ['d' => 1, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-09-01', 'is_lottery' => true],
              ['d' => 8, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'date' => '2026-09-08'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-09-16', 'is_lottery' => true],
              ['d' => 21, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน)', 'date' => '2026-09-21'],
              ['d' => 25, 't' => '09:00', 'type' => 'วันมู', 'topic' => 'วันไหว้พระจันทร์: เลขเมตตามหานิยม', 'date' => '2026-09-25'],
              ['d' => 30, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(ปิดท้ายเดือน)', 'date' => '2026-09-30'],
          ]],
          ['month' => 'ตุลาคม 69', 'items' => [
              ['d' => 1, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-10-01', 'is_lottery' => true],
              ['d' => 8, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'date' => '2026-10-08'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-10-16', 'is_lottery' => true],
              ['d' => 20, 't' => '09:00', 'type' => 'วันมู', 'topic' => 'เทศกาลกินเจ: เลขสายขาว (เลือกลงวันที่ 20)', 'date' => '2026-10-20'],
              ['d' => 23, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันปิยมหาราช: เลขมงคลการงาน', 'date' => '2026-10-23'],
          ]],
          ['month' => 'พฤศจิกายน 69', 'items' => [
              ['d' => 1, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-11-01', 'is_lottery' => true],
              ['d' => 8, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'date' => '2026-11-08'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-11-16', 'is_lottery' => true],
              ['d' => 24, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันลอยกระทง: เลขขอพรโชคลาภ', 'date' => '2026-11-24'],
              ['d' => 29, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน)', 'date' => '2026-11-29'],
          ]],
          ['month' => 'ธันวาคม 69', 'items' => [
              ['d' => 1, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-12-01', 'is_lottery' => true],
              ['d' => 5, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันพ่อ: เลขมงคลความมั่นคง', 'date' => '2026-12-05'],
              ['d' => 10, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันรัฐธรรมนูญ: เลขมงคลระเบียบวินัย', 'date' => '2026-12-10'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2026-12-16', 'is_lottery' => true],
              ['d' => 24, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางก่อนปีใหม่)', 'date' => '2026-12-24'],
              ['d' => 31, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันสิ้นปี: สรุปเลขปี 69', 'date' => '2026-12-31'],
          ]],
          ['month' => 'มกราคม 70', 'items' => [
              ['d' => 1, 't' => '09:00', 'type' => 'หวย/สำคัญ', 'topic' => 'วันขึ้นปีใหม่: เปิดดวงตัวเลขปี 70 + หวย', 'date' => '2027-01-01', 'is_lottery' => true],
              ['d' => 9, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันเด็ก: เลขมงคลเสริม IQ', 'date' => '2027-01-09'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2027-01-16', 'is_lottery' => true],
              ['d' => 23, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(อุดช่องว่างปลายเดือน)', 'date' => '2027-01-23'],
          ]],
          ['month' => 'กุมภาพันธ์ 70', 'items' => [
              ['d' => 1, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2027-02-01', 'is_lottery' => true],
              ['d' => 6, 't' => '09:00', 'type' => 'วันมู', 'topic' => 'วันตรุษจีน: เลขรับทรัพย์', 'date' => '2027-02-06'],
              ['d' => 14, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันวาเลนไทน์: คู่เลขความรัก', 'date' => '2027-02-14'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2027-02-16', 'is_lottery' => true],
              ['d' => 21, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันมาฆบูชา: เลขสายบุญ', 'date' => '2027-02-21'],
              ['d' => 26, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(อุดช่องว่างปลายเดือน)', 'date' => '2027-02-26'],
          ]],
          ['month' => 'มีนาคม 70', 'items' => [
              ['d' => 1, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2027-03-01', 'is_lottery' => true],
              ['d' => 8, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน - เดือนนี้ไม่มีเทศกาล)', 'date' => '2027-03-08'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2027-03-16', 'is_lottery' => true],
              ['d' => 24, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน)', 'date' => '2027-03-24'],
              ['d' => 30, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(ปิดท้ายเดือน)', 'date' => '2027-03-30'],
          ]],
          ['month' => 'เมษายน 70', 'items' => [
              ['d' => 1, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2027-04-01', 'is_lottery' => true],
              ['d' => 6, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันจักรี: เลขเสริมอำนาจ', 'date' => '2027-04-06'],
              ['d' => 13, 't' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันสงกรานต์: เลขปลอดภัยในการเดินทาง', 'date' => '2027-04-13'],
              ['d' => 16, 't' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'date' => '2027-04-16', 'is_lottery' => true],
              ['d' => 24, 't' => '09:09', 'type' => 'Evergreen', 'topic' => '(อุดช่องว่างปลายเดือน)', 'date' => '2027-04-24'],
          ]],
      ];

      $totalPlanned = 0;
      $totalDone = 0;
      foreach ($planData as $month) {
          $totalPlanned += count($month['items']);
      }
      
      // Helper function to check if article exists for date or lottery
      $checkDone = function($item) use ($existingArticlesInfo, &$totalDone) {
          $exists = false;
          foreach ($existingArticlesInfo as $article) {
              // Check by date
              if ($article['date'] === $item['date']) {
                  $exists = true; break;
              }
              // Special check for Lottery
              if (!empty($item['is_lottery'])) {
                  $dateObj = \Carbon\Carbon::parse($item['date']);
                  $isRound1 = (int)$dateObj->format('j') <= 15;
                  $pattern = '/^thai-goverment-lottery-' . $dateObj->format('Ym') . ($isRound1 ? 'first' : 'second') . '$/';
                  if (preg_match($pattern, (string)$article['slug'])) {
                      $exists = true; break;
                  }
              }
          }
          if ($exists) $totalDone++;
          return $exists;
      };
    @endphp

    @if(!$tableExists)
      <div class="admin-alert admin-alert--warning" style="margin-bottom: 20px; background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; padding: 15px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px; height: 24px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
        <div>
          <strong style="display: block;">ยังไม่ได้รัน Migration!</strong>
          <span style="font-size: 14px;">กรุณารันคำสั่ง <code>php artisan migrate</code> เพื่อเปิดใช้งานระบบแผนการเผยแพร่บทความครับ</span>
        </div>
      </div>
    @endif

    <section class="admin-card content-plan-card">
      <div class="plan-header">
        <h3 class="plan-title">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px; height: 24px; color: #7c3aed;"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" /></svg>
          ตารางแผนการเผยแพร่บทความ (1 ม.ค. - 31 ธ.ค. {{ now()->format('Y') + 543 }})
        </h3>
        <div style="display: flex; align-items: center; gap: 15px;">
          @if($isManager)
            <button type="button" class="admin-button" style="background: #7c3aed; padding: 6px 12px; font-size: 13px;" onclick="openAddPlanModal()">+ เพิ่มแผนงาน</button>
          @endif
          <div class="plan-status-summary">
            เตรียมแล้ว <span style="color: #10b981;">{{ $totalDone }}</span> / ทั้งหมด {{ $totalPlanned }}
          </div>
        </div>
      </div>

      <div class="plan-table-wrap">
        <table class="plan-table">
          <thead>
            <tr>
              <th style="width: 140px;">เดือน</th>
              <th style="width: 140px;">วันที่ / เวลา</th>
              <th style="width: 110px;">ประเภท</th>
              <th>หัวข้อ</th>
              @if($isManager)
                <th style="width: 80px;">จัดการ</th>
              @endif
            </tr>
          </thead>
          <tbody>
            @foreach($groupedPlans as $monthName => $items)
              <tr class="month-divider" data-month-divider="{{ $monthName }}">
                <td colspan="{{ $isManager ? 5 : 4 }}">{{ $monthName }}</td>
              </tr>
              @foreach($items as $item)
                @php $isDone = $checkItemDone($item); @endphp
                <tr class="{{ $isDone ? 'plan-row--checked' : ($item->type === 'หวย' ? 'plan-row--lottery' : '') }} plan-item-row" data-month="{{ $monthName }}">
                  <td>{{ $monthName }}</td>
                  <td style="font-weight: 700;">
                    @if($isDone) 
                      <span class="check-icon">✅</span> 
                    @elseif($item->type === 'หวย')
                      <span style="color: #EAB308; margin-right: 4px; font-weight: 900;">!</span>
                    @endif
                    {{ \Carbon\Carbon::parse($item->publish_date)->format('j') }} | {{ $item->publish_time }}
                  </td>
                  <td><span class="type-pill type-pill--{{ strtolower($item->type) }}">{{ $item->type }}</span></td>
                  <td>{{ $item->topic }}</td>
                  @if($isManager)
                    <td>
                      <div style="display: flex; gap: 8px;">
                        <button type="button" class="plan-action-btn" onclick="openEditPlanModal({{ $item->toJson() }})" title="แก้ไข">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                        </button>
                        <form action="{{ route('admin.article-plans.destroy', $item) }}" method="POST" onsubmit="return confirm('ยืนยันการลบแผนงานนี้?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="plan-action-btn plan-action-btn--delete" title="ลบ">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5a3.375 3.375 0 00-3.375 3.375V6.75m7.5 0H9" /></svg>
                          </button>
                        </form>
                      </div>
                    </td>
                  @endif
                </tr>
              @endforeach
            @endforeach
          </tbody>
        </table>
      </div>
    </section>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const monthFilter = document.getElementById('plan-month-filter');
        const searchInput = document.getElementById('article-search');
        const articleRows = document.querySelectorAll('.article-row');
        const planRows = document.querySelectorAll('.plan-item-row');
        const dividers = document.querySelectorAll('.month-divider');
        const emptyRow = document.getElementById('articles-empty-row');
        const countDisplay = document.querySelector('.article-admin-toolbar__count');

        function applyFilters() {
          const selectedMonth = monthFilter ? monthFilter.value : '';
          const searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';
          
          // 1. Filter Main Articles Table
          let visibleArticleCount = 0;
          articleRows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            const slug = row.getAttribute('data-slug') || '';
            const rowMonthYear = row.getAttribute('data-month-year') || '';

            const searchMatch = !searchQuery || title.includes(searchQuery) || slug.includes(searchQuery);
            const monthMatch = !selectedMonth || rowMonthYear === selectedMonth;

            if (searchMatch && monthMatch) {
              row.style.display = '';
              visibleArticleCount++;
            } else {
              row.style.display = 'none';
            }
          });

          if (emptyRow) {
            emptyRow.style.display = (visibleArticleCount === 0 && articleRows.length > 0) ? "" : "none";
          }
          
          if (countDisplay) {
            countDisplay.innerText = `แสดง ${visibleArticleCount} จากทั้งหมด ${articleRows.length} รายการ`;
          }

          // 2. Filter Roadmap Table
          if (!selectedMonth) {
            planRows.forEach(row => row.style.display = '');
            dividers.forEach(div => div.style.display = '');
          } else {
            planRows.forEach(row => {
              row.style.display = row.getAttribute('data-month') === selectedMonth ? '' : 'none';
            });
            dividers.forEach(div => {
              div.style.display = div.getAttribute('data-month-divider') === selectedMonth ? '' : 'none';
            });
          }
        }

        if (monthFilter) monthFilter.addEventListener('change', applyFilters);
        if (searchInput) searchInput.addEventListener('input', applyFilters);
        
        // Initial filter on load
        applyFilters();
      });
    </script>

    {{-- AI Prompt Modal --}}
    <div id="ai-prompt-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10001; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
        <div style="background: white; width: 100%; max-width: 500px; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
            <div style="padding: 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(to right, #f8fafc, #ffffff);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); display: flex; align-items: center; justify-content: center; color: white;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px; height: 24px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.456-2.455l.259-1.036.259 1.036a3.375 3.375 0 002.455 2.456l1.036.259-1.036.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                        </svg>
                    </div>
                    <h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #1e293b; font-family: 'Kanit', sans-serif;">AI Prompt Generator</h3>
                </div>
                <button type="button" onclick="closeAiPromptModal()" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: all 0.2s;">&times;</button>
            </div>
            <div style="padding: 24px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; color: #475569; font-size: 15px;">หัวข้อบทความที่ต้องการ</label>
                <input type="text" id="ai-subject" class="admin-input" style="width: 100%; border-radius: 12px; padding: 12px 16px; border: 2px solid #e2e8f0; font-size: 16px; margin-bottom: 24px;" placeholder="เช่น ใครสวยที่สุดในปฐพี..." onkeypress="if(event.key === 'Enter') copyAiPrompt('short')">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <button type="button" onclick="copyAiPrompt('short')" style="background: #ffffff; color: #4f46e5; border: 2px solid #e0e7ff; padding: 16px; border-radius: 16px; font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; gap: 8px;" onmouseover="this.style.background='#f5f7ff'" onmouseout="this.style.background='#ffffff'">
                        <span style="font-size: 14px; opacity: 0.8;">แบบที่ 1</span>
                        <span style="font-size: 16px;">บทความสั้น</span>
                    </button>
                    <button type="button" onclick="copyAiPrompt('long')" style="background: #4f46e5; color: #ffffff; border: 2px solid #4f46e5; padding: 16px; border-radius: 16px; font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        <span style="font-size: 14px; opacity: 0.9;">แบบที่ 2</span>
                        <span style="font-size: 16px;">บทความยาว (1000+ คำ)</span>
                    </button>
                </div>
                <p id="copy-status" style="text-align: center; margin-top: 20px; font-size: 14px; font-weight: 600; color: #10b981; display: none;">✓ คัดลอก Prompt เรียบร้อยแล้ว!</p>
            </div>
            <div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #f1f5f9; color: #94a3b8; font-size: 12px; text-align: center;">
                ก๊อปปี้แล้วเอาไปวางใน ChatGPT หรือ Claude ได้เลย
            </div>
        </div>
    </div>

    {{-- Article Plan Modal --}}
    <div id="plan-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
        <div style="background: white; width: 100%; max-width: 500px; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
            <div style="padding: 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 id="plan-modal-title" style="margin: 0; font-size: 20px; font-weight: 800; color: #1e293b;">เพิ่มแผนการเผยแพร่</h3>
                <button type="button" onclick="closePlanModal()" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <form id="plan-form" action="" method="POST">
                @csrf
                <input type="hidden" id="plan-method" name="_method" value="POST">
                <input type="hidden" id="plan-id" name="id" value="">
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 6px; font-weight: 700; color: #475569; font-size: 14px;">วันที่</label>
                            <input type="date" id="plan-date" name="publish_date" class="admin-input" style="width: 100%; font-size: 16px;" required>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 6px; font-weight: 700; color: #475569; font-size: 14px;">เวลา</label>
                            <input type="text" id="plan-time" name="publish_time" class="admin-input" style="width: 100%; font-size: 16px;" placeholder="09:00" required>
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 700; color: #475569; font-size: 14px;">ประเภท</label>
                        <select id="plan-type" name="type" class="admin-input" style="width: 100%; font-size: 16px;">
                            <option value="วันสำคัญ">วันสำคัญ</option>
                            <option value="หวย">หวย</option>
                            <option value="Evergreen">Evergreen</option>
                            <option value="วันมู">วันมู</option>
                            <option value="บทวิเคราะห์">บทวิเคราะห์</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 700; color: #475569; font-size: 14px;">หัวข้อ</label>
                        <input type="text" id="plan-topic" name="topic" class="admin-input" style="width: 100%; font-size: 16px;" placeholder="เช่น วันพืชมงคล..." required>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="plan-lottery" name="is_lottery" value="1" style="width: 18px; height: 18px;">
                        <label for="plan-lottery" style="font-weight: 700; color: #475569; font-size: 14px;">เป็นคอนเทนต์หวย (เพื่อตรวจสอบ Slug อัตโนมัติ)</label>
                    </div>
                </div>
                <div style="padding: 20px 24px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" onclick="closePlanModal()" class="admin-button admin-button--muted" style="border-radius: 12px; font-weight: 700;">ยกเลิก</button>
                    <button type="submit" class="admin-button" style="background: #7c3aed; border-color: #7c3aed; border-radius: 12px; font-weight: 700; padding-left: 24px; padding-right: 24px;">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Import JSON Modal --}}
    <div id="import-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
        <div style="background: white; width: 100%; max-width: 600px; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
            <div style="padding: 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #1e293b;">นำเข้าบทความด้วย JSON</h3>
                <button type="button" onclick="closeImportModal()" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <form action="{{ route('admin.articles.import-json') }}" method="POST">
                @csrf
                <div style="padding: 24px;">
                    <p style="margin-top: 0; margin-bottom: 15px; color: #64748b; font-size: 14px; font-weight: 500;">วางข้อมูล JSON ของบทความที่นี่ (สามารถใส่เป็นชิ้นเดียว หรือเป็น Array ของหลายบทความได้)</p>
                    <textarea name="json_data" class="admin-input" style="width: 100%; height: 300px; font-family: 'JetBrains Mono', monospace; font-size: 16px; padding: 15px; border-radius: 16px; border: 2px solid #e2e8f0;" placeholder='[
  {
    "title": "หัวข้อบทความ",
    "content": "<p>เนื้อหาบทความ</p>",
    "is_published": true
  }
]' required></textarea>
                </div>
                <div style="padding: 20px 24px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" onclick="closeImportModal()" class="admin-button admin-button--muted" style="border-radius: 12px; font-weight: 700;">ยกเลิก</button>
                    <button type="submit" class="admin-button" style="background: #7c3aed; border-color: #7c3aed; border-radius: 12px; font-weight: 700; padding-left: 24px; padding-right: 24px;">ยืนยันการนำเข้า</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/canvg@3.0.10/lib/umd.js"></script>
  
    <script>
    (function() {
        // --- ส่วนควบคุมการแสดงผลและพื้นที่วาดรูป ---
        const overlay = document.getElementById('render-overlay'); // หน้าจอสีดำตอนกำลังวาดรูป
        const status = document.getElementById('render-status');   // ข้อความสถานะการวาด
        const canvas = document.getElementById('render-canvas');   // พื้นที่วาดรูป (ซ่อนไว้)
        const ctx = canvas.getContext('2d');

        /**
         * ฟังก์ชันกลางสำหรับวาดรูปพรีเมียมและอัปโหลดขึ้น Server
         * คืนค่าเป็นข้อมูลรูปภาพที่อัปโหลดเสร็จแล้ว
         */
        async function renderAndUploadPremiumImage(imagePath, uploadUrl, loadingText, reportUrl, type = 'square') {
            if (!imagePath) {
                return null;
            }

            // 1. เตรียม URL รูปวาดผ่าน Proxy
            const svgPath = imagePath.replace(/\.(png|jpg|jpeg|webp)$/i, '.svg');
            const svgUrl = `{{ route('admin.articles.get-svg-proxy') }}?path=${encodeURIComponent(svgPath)}`;
            
            if (!svgUrl || !svgUrl.toLowerCase().includes('.svg')) {
                return null; // ถ้าไม่มีไฟล์รูปวาด ให้ระบบเดิมทำงานต่อ
            }

            const canvgObj = window.canvg || window.Canvg;
            if (!canvgObj) {
                alert('ระบบวาดรูปยังไม่พร้อม กรุณารอ 2 วินาทีแล้วลองใหม่ครับ');
                return null;
            }

            overlay.style.display = 'flex';
            status.innerText = loadingText;

            try {
                // 2. ดึงและแปลงฟอนต์
                const fontRes = await fetch('/fonts/Kanit-700.ttf');
                const fontBlob = await fontRes.blob();
                const fontBase64 = await new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.readAsDataURL(fontBlob);
                });

                // 3. ดึงโค้ดรูปวาด
                const response = await fetch(svgUrl);
                if (!response.ok) throw new Error('ไม่สามารถดึงข้อมูลรูปภาพจาก Server ได้ (SVG Not Found)');
                let svgText = await response.text();

                // 4. ฉีดฟอนต์ (รองรับทั้งชื่อ Kanit และ KanitCustom)
                const fontStyle = `<style>@font-face { font-family: 'Kanit'; src: url("${fontBase64}"); font-weight: 700; } @font-face { font-family: 'KanitCustom'; src: url("${fontBase64}"); font-weight: 700; }</style>`;
                svgText = svgText.replace('<defs>', `<defs>${fontStyle}`);

                canvas.width = 1200;
                canvas.height = 1200;
                ctx.fillStyle = "black";
                ctx.fillRect(0, 0, 1200, 1200);

                // 5. เริ่มการวาดรูป
                if (typeof canvgObj === 'function') {
                    await canvgObj(canvas, svgText);
                } else if (canvgObj.Canvg && typeof canvgObj.Canvg.fromString === 'function') {
                    const v = await canvgObj.Canvg.fromString(ctx, svgText);
                    await v.render();
                } else {
                    const v = await canvgObj.fromString(ctx, svgText);
                    await v.render();
                }

                // 6. แปลงเป็น PNG และอัปโหลด
                const pngData = canvas.toDataURL('image/png', 0.9);
                status.innerText = 'กำลังบันทึกรูปพรีเมียม...';
                
                const uploadRes = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ image: pngData, type })
                });

                const uploadJson = await uploadRes.json();
                if (!uploadJson.success) throw new Error(uploadJson.error || 'Upload failed');

                return uploadJson;

            } catch (err) {
                handleRenderError(err, overlay, reportUrl);
                return null;
            }
        }

        /**
         * ฟังก์ชันแปลงรูปและแชร์ไป Facebook Page
         */
        window.renderAndShareSocial = async function(button, articleId, imagePath, uploadUrl, reportUrl, isComplete = 1) {
            // ตรวจสอบกรณีเป็นหวยแต่ยังออกไม่ครบ
            if (isComplete === 0) {
                alert('⚠️ หวยงวดนี้ยังออกไม่ครบ 100% กรุณารอให้ระบบอัปเดตผลรางวัลให้ครบถ้วนก่อนจึงจะแชร์ได้ครับ');
                return;
            }

            const form = document.getElementById('share-social-form-' + articleId);
            const imageInput = document.getElementById('share-social-image-' + articleId);

            if (!confirm('ยืนยันแปลงรูปเป็น PNG และแชร์ไปที่ Facebook Page?')) {
                return;
            }

            button.disabled = true;
            const originalText = button.innerText;
            button.innerText = 'กำลังทำงาน...';

            try {
                if (imagePath && !imagePath.toLowerCase().endsWith('.svg')) {
                    imageInput.value = imagePath;
                    form.submit();
                    return;
                }

                const result = await renderAndUploadPremiumImage(imagePath, uploadUrl, 'กำลังแปลงรูปและเตรียมแชร์ Facebook...', reportUrl, 'square');

                if (result) {
                    imageInput.value = result.path;
                    status.innerText = 'สำเร็จ! กำลังแชร์ไปที่ Facebook...';
                    setTimeout(() => form.submit(), 1000);
                } else {
                    console.log('Premium rendering failed, aborting social share.');
                    button.disabled = false;
                    button.innerText = originalText;
                }
            } catch (err) {
                console.error('Share social error:', err);
                button.disabled = false;
                button.innerText = originalText;
                alert('แชร์ไม่สำเร็จ: ' + (err.message || err));
            }
        };

        /**
         * ฟังก์ชันแชร์ไป Facebook (คงไว้สำหรับลิงก์เก่าหรือ auto flow เดิม)
         */
        window.shareToFb = async function(button, articleId, imagePath, uploadUrl, reportUrl) {
            const form = document.getElementById('share-fb-form-' + articleId);
            const imageInput = document.getElementById('share-fb-image-' + articleId);

            if (!form || !imageInput) {
                return window.renderAndShareSocial(button, articleId, imagePath, uploadUrl, reportUrl);
            }
            
            // ตรวจสอบว่าเป็นบทความทั่วไปหรือไม่ (ถ้าไม่ใช่ .svg แสดงว่าเป็นรูปปกติ)
            if (imagePath && !imagePath.toLowerCase().endsWith('.svg')) {
                console.log('Standard article detected, sharing directly without rendering.');
                imageInput.value = imagePath;
                form.submit();
                return;
            }

            const result = await renderAndUploadPremiumImage(imagePath, uploadUrl, 'กำลังเตรียมรูปภาพสำหรับ Facebook...', reportUrl, 'square');
            
            if (result) {
                imageInput.value = result.path;
                status.innerText = 'สำเร็จ! กำลังไปที่ Facebook...';
                setTimeout(() => form.submit(), 1000);
            } else {
                // สำหรับ Facebook หากพรีเมียมขัดข้อง เราจะไม่แชร์ต่อตามความต้องการของ USER
                console.log('Premium rendering failed, aborting FB share per user request.');
            }
        };

        /**
         * ฟังก์ชันแชร์ไป LINE (เรียกใช้ตัวกลาง)
         */
        window.shareToLine = async function(button, articleId, imagePath, uploadUrl, reportUrl, prefix = 'share') {
            const form = document.getElementById(prefix + '-line-form-' + articleId);
            const imageInput = document.getElementById(prefix + '-line-image-' + articleId);

            if (!form || !imageInput) {
                return window.renderAndShareSocial(button, articleId, imagePath, uploadUrl, reportUrl);
            }
            
            // For broadcast, we show confirmation before rendering
            if (prefix === 'broadcast') {
                if (!confirm('ยืนยันการ Broadcast ส่งหาผู้ติดตามทุกคน? หากส่งแล้วจะไม่สามารถดึงข้อความกลับได้')) {
                    return;
                }
            }

            // ตรวจสอบว่าเป็นบทความทั่วไปหรือไม่
            if (imagePath && !imagePath.toLowerCase().endsWith('.svg')) {
                console.log('Standard article detected, sharing to LINE directly.');
                imageInput.value = imagePath;
                form.submit();
                return;
            }
            
            const result = await renderAndUploadPremiumImage(imagePath, uploadUrl, 'กำลังเตรียมรูปภาพสำหรับ LINE...', reportUrl, 'square');
            
            if (result) {
                imageInput.value = result.path;
                status.innerText = 'สำเร็จ! กำลังส่งเข้า LINE...';
                setTimeout(() => form.submit(), 1000);
            } else {
                form.submit(); // สำหรับ LINE ให้ส่งแบบธรรมดาแทนเพื่อความต่อเนื่อง
            }
        };

        /**
         * ฟังก์ชันจัดการเมื่อการวาดรูปขัดข้อง
         */
        function handleRenderError(err, overlay, reportUrl) {
            console.error('Render error:', err);
            overlay.style.display = 'none';
            alert('การวาดรูปขัดข้อง ระบบจะส่งข้อมูลแบบปกติให้แทนครับ');
            
            if (reportUrl) {
                fetch(reportUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ error: err.message, stack: err.stack })
                });
            }
        }

        /**
         * ระบบ Auto-Trigger: ตรวจสอบคำสั่งจาก URL เพื่อกดปุ่มแชร์อัตโนมัติ
         * ตัวอย่างลิงก์: .../admin/articles?auto_share=fb&article_id=123
         */
        window.addEventListener('load', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const autoShare = urlParams.get('auto_share'); // 'fb' หรือ 'line'
            const articleId = urlParams.get('article_id');

            if (autoShare && articleId) {
                const buttonId = `btn-share-social-${articleId}`;
                const button = document.getElementById(buttonId);
                
                if (button) {
                    console.log(`Auto-triggering ${autoShare} share for article ${articleId}`);
                    // หน่วงเวลาเล็กน้อยเพื่อให้ระบบ Canvg พร้อมทำงาน
                    setTimeout(() => button.click(), 1500);
                }
            }
        });
    })();


    function openAiPromptModal() {
        document.getElementById('ai-prompt-modal').style.display = 'flex';
        document.getElementById('ai-subject').focus();
        document.body.style.overflow = 'hidden';
    }

    function closeAiPromptModal() {
        document.getElementById('ai-prompt-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function copyAiPrompt(type) {
        const subject = document.getElementById('ai-subject').value || '.....................';
        const now = new Date();
        const formattedDate = now.getFullYear() + '-' + 
                            String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(now.getDate()).padStart(2, '0') + ' 09:00:00';
        
        const constraints = type === 'long' 
            ? 'CONTENT_MUST_BE_1000_WORDS_MINIMUM | NO_YEAR_IN_SLUG | HTML_FORMAT_ONLY'
            : 'NO_YEAR_IN_SLUG | HTML_FORMAT_ONLY';

        const promptTemplate = `ช่วยเขียนบทความเกี่ยวกับ ${subject} หาจากแหล่งข้อมูลที่น่าเชื่อถือเท่านั้น ใน Format [
  {
   "_constraints": "${constraints}",
    "title": "พาดหัวที่ดึงดูดใจ (ใส่ปี พ.ศ. ได้)",
    "slug": "url-slug-no-year",
    "excerpt": "คำเกริ่นนำสั้นๆ",
    "content": "เนื้อหา HTML (ใช้ <h2>, <h3>, <p>, <ul>, <li>)",
    "meta_description": "สรุปเนื้อหาสำหรับ Google Search (120-155 characters)",
    "keywords": "Focus Keyword หลัก 5 คำ",
    "lsi_keywords": "ใส่ LSI Keywords คั่นด้วยจุลภาค 10 คำ" ,
    "is_published": false,
    "published_at": "${formattedDate}",
    "is_auto_post": true,
    "image_guidelines": {
      "landscape_prompt": "Prompt สำหรับรูปที่ relate กับรูป 16:9",
      "square_prompt": "Prompt สำหรับรูปที่ relate กับรูป 16:9"
    }
  }
]`;

        navigator.clipboard.writeText(promptTemplate).then(() => {
            const status = document.getElementById('copy-status');
            status.style.display = 'block';
            setTimeout(() => {
                status.style.display = 'none';
                closeAiPromptModal();
            }, 1500);
        });
    }

    function openImportModal() {
        document.getElementById('import-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeImportModal() {
        document.getElementById('import-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function openAddPlanModal() {
        document.getElementById('plan-modal-title').innerText = 'เพิ่มแผนการเผยแพร่';
        document.getElementById('plan-method').value = 'POST';
        document.getElementById('plan-form').action = "{{ route('admin.article-plans.store') }}";
        document.getElementById('plan-id').value = '';
        document.getElementById('plan-date').value = '';
        document.getElementById('plan-time').value = '09:00';
        document.getElementById('plan-type').value = 'วันสำคัญ';
        document.getElementById('plan-topic').value = '';
        document.getElementById('plan-lottery').checked = false;
        
        document.getElementById('plan-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function openEditPlanModal(item) {
        document.getElementById('plan-modal-title').innerText = 'แก้ไขแผนการเผยแพร่';
        document.getElementById('plan-method').value = 'PUT';
        document.getElementById('plan-form').action = "/admin/article-plans/" + item.id;
        document.getElementById('plan-id').value = item.id;
        document.getElementById('plan-date').value = item.publish_date.split('T')[0];
        document.getElementById('plan-time').value = item.publish_time;
        document.getElementById('plan-type').value = item.type;
        document.getElementById('plan-topic').value = item.topic;
        document.getElementById('plan-lottery').checked = !!item.is_lottery;
        
        document.getElementById('plan-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closePlanModal() {
        document.getElementById('plan-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
  </script>
@endpush
