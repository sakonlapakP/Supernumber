# Content Refresh/Optimization - Quick Reference Guide

## The Problem in 60 Seconds

**Year-based image storage + Annual article updates = Broken links and hidden articles**

```
2025: Article created → images saved to articles/2025/slug/...
2026: Article refreshed → images saved to articles/2026/slug/... BUT article not found in 2026 searches!
```

---

## Key Files to Understand

| File | Lines | Purpose |
|------|-------|---------|
| `app/Services/FacebookContentRefreshService.php` | 712 | Stores images with year in path |
| `app/Http/Controllers/Api/ArticleController.php` | 18-42 | Filters articles by YEAR in published_at |
| `app/Console/Commands/FetchLatestLotteryCommand.php` | 228 | Lottery articles also use year paths |
| `database/migrations/2026_05_08_070715_...` | - | Timestamps converted to unix bigint |

---

## The Core Issue

### Image Storage (Year-based)
```php
// PROBLEM: Images go to year-specific directories
$path = "articles/{$year}/{$slug}/facebook-cover-{$post->id}.{$ext}";
// Examples:
// 2025: articles/2025/new-year-lucky-numbers/image.jpg
// 2026: articles/2026/new-year-lucky-numbers/image.jpg (same article!)
```

### Article Queries (Year-based)
```php
// PROBLEM: Filters by YEAR of published_at
$query->whereYear('published_at', $yearFull)
      ->whereMonth('published_at', $monthNum);

// But Content Refresh PRESERVES old published_at for SEO!
// So 2026 refresh of 2025 article won't be found in 2026 queries
```

### The Collision
```
Article: chinese-new-year-wealth-numbers
├─ published_at: 2025-02-08 (original, preserved for SEO)
├─ cover_image_path: articles/2026/...  (new refresh)
└─ content: 2026 Facebook posts

Query: "Give me articles from January 2026"
Result: ❌ Empty (published_at is February 2025, not 2026!)

Images: "Where's articles/2025/...?"
Result: ❌ 404 (moved to articles/2026/...)
```

---

## Three Solutions Ranked

### ⭐ Solution 1: Decouple Storage (RECOMMENDED)
**Store images by slug only, not year**

```php
// Change from:
$path = "articles/{$year}/{$slug}/image.jpg"

// To:
$path = "articles/content/{$slug}/image.jpg"
```

✅ Simple | ✅ Fixes root cause | ✅ Low risk  
⚠️ Requires file migration

---

### Solution 2: Track Versions Separately
**Create ArticleVersion table for yearly updates**

```php
article -> article_versions (one-to-many)
// Each year = new row in versions table
```

✅ Clean structure | ✅ Full history  
❌ Major refactor | ❌ High risk | ❌ Complex queries

---

### Solution 3: Alias System
**Map old paths to new paths**

```php
articles/2025/slug/image.jpg ──alias──> articles/2026/slug/image.jpg
```

✅ Quick interim fix | ✅ No migration  
❌ Doesn't fix root cause | ❌ Added complexity

---

## Quick Diagnostics

### Check if affected:
```bash
# Find articles with content from new year but old published_at
mysql> SELECT id, slug, published_at, cover_image_path 
       FROM articles 
       WHERE published_at < '2026-01-01' 
       AND cover_image_path LIKE '%2026%';
```

### Common Symptoms:
- ❌ Articles not appearing in monthly filters
- ❌ 404 errors on article images
- ❌ "ArticlePlan topic matches but no article appears" in refresh logs
- ❌ SEO traffic unchanged despite updated content

---

## Affected Database Columns

| Table | Column | Issue |
|-------|--------|-------|
| articles | published_at | Unix timestamp, `YEAR()` may not work |
| articles | cover_image_path | Year in path |
| articles | cover_image_square_path | Year in path |
| articles | cover_image_landscape_path | Year in path |
| article_plans | publish_date | Filters by year via `whereYear()` |

---

## Next Steps (Priority Order)

1. **Audit** - Run diagnostics above to find affected articles
2. **Plan** - Decide: Solution 1 (recommended), 2, or 3
3. **Implement** - Deploy chosen solution
4. **Test** - Cross-year refresh scenarios
5. **Migrate** - Move existing images if needed
6. **Deploy** - Roll out gradually with monitoring

---

## Critical Code Locations

```
🔴 Image Storage (NEEDS FIXING):
   app/Services/FacebookContentRefreshService.php:712
   app/Console/Commands/FetchLatestLotteryCommand.php:228
   app/Console/Commands/SyncArticlesFromFacebookByTitleCommand.php:~90

🔴 Retrieval Queries (NEEDS FIXING):
   app/Http/Controllers/Api/ArticleController.php:41-42
   app/Http/Controllers/Api/ArticlePlanController.php:18

🟡 Timestamp Issues (AUDIT REQUIRED):
   All whereYear(), whereMonth(), whereDate() on articles table

🟢 Safe:
   Article model, relationships, basic CRUD
```

---

## Impact Summary

| Component | Impact | Workaround |
|-----------|--------|-----------|
| Monthly article filters | ❌ Missing refreshed articles | Query by slug instead |
| Image links | ❌ 404 errors | Manually update cover_image_* paths |
| Content Refresh feature | ⚠️ Partially broken | Only works for new articles, not refreshes |
| SEO | ✅ OK if published_at preserved | But articles hidden from queries! |

---

## Example: The "วันตรุษจีน" Problem

| Stage | Time | Action | Result |
|-------|------|--------|--------|
| Initial | Feb 2025 | Create article for Chinese New Year 2025 | ✅ Articles table: `published_at=2025-02-08`, `cover_image_path=articles/2025/chinese-new-year/...` |
| Refresh | Jan 2026 | Facebook posts imported, ContentRefresh runs | ✅ Updated content, image moved, BUT `published_at=2025-02-08` |
| Query | Jan 2026 | User asks for articles from "มกราคม 68" (Jan 2025) | ✅ Found (still 2025) |
| Query | Jan 2026 | User asks for articles from "มกราคม 69" (Jan 2026) | ❌ NOT FOUND (`published_at` is 2025!) |
| Images | Jan 2026 | Frontend loads `<img src="articles/2025/...">` | ❌ 404 (images now in `articles/2026/...`) |

---

## Database Schema Reference

```sql
-- Key columns to watch:
articles:
  ├─ id (PK)
  ├─ slug (unique) ← Used in image paths
  ├─ published_at (unix timestamp) ← Used for year filtering
  ├─ cover_image_path ← Contains year
  ├─ cover_image_square_path ← Contains year
  └─ cover_image_landscape_path ← Contains year

article_plans:
  ├─ topic (e.g., "วันตรุษจีน") ← Matched to articles
  └─ publish_date ← Filtered by year

facebook_imported_posts:
  ├─ message, story ← Content for refresh
  └─ facebook_created_time ← Used to determine image year
```

---

## Detection Query

```sql
-- Find articles that were refreshed (new year images on old published_at):
SELECT 
  a.id,
  a.slug,
  YEAR(FROM_UNIXTIME(a.published_at)) as published_year,
  RIGHT(a.cover_image_path, 10) as image_path_start
FROM articles a
WHERE a.cover_image_path LIKE CONCAT('articles/', YEAR(NOW()), '%')
AND YEAR(FROM_UNIXTIME(a.published_at)) < YEAR(NOW());
-- If this returns rows, you have cross-year refresh artifacts
```

---

## Prevention (For Future Development)

- ❌ Never hardcode year in file paths
- ❌ Never use `whereYear()` to filter articles unless specifically for historical archive
- ✅ Use slug-based paths for static content
- ✅ Consider versioning from the start for recurring content
- ✅ Document SEO implications of publication date preservation

---

**For Full Analysis:** See `CONTENT_REFRESH_ANALYSIS.md`  
**For Implementation Plan:** See project documentation
