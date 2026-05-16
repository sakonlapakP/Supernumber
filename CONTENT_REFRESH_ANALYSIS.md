# Supernumber Article Database: Content Refresh/Optimization Analysis

**Project:** Supernumber (Thai Lucky Number Marketplace with SEO-driven Content)  
**Analysis Date:** May 15, 2026  
**Focus:** Article database schema, year-based data separation, and Content Refresh feature issues

---

## Executive Summary

The **Content Refresh/Optimization** feature enables updating articles with new Facebook content while preserving original publication dates for SEO authority. However, the current implementation uses **year-based data separation** at multiple layers (storage paths, filtering queries), which creates critical issues when:

1. Articles span multiple years (e.g., annual holidays like "วันตรุษจีน")
2. Data retrieval logic uses `whereYear()` filters that don't account for cross-year articles
3. Image/asset paths are hardcoded with year directories, breaking links during refresh

This analysis identifies the root causes and proposes solutions.

---

## Section 1: Current Database Architecture

### 1.1 Articles Table Schema

**Location:** `database/migrations/2026_03_08_120000_create_articles_table.php`

```
articles (primary storage)
├── id (bigint, primary key)
├── title (varchar 255)
├── slug (varchar, unique)
├── excerpt (text, nullable)
├── content (longtext)
├── cover_image_path (varchar, nullable) — primary cover image
├── cover_image_square_path (varchar, nullable) — 1:1 aspect ratio
├── cover_image_landscape_path (varchar, nullable) — 16:9 aspect ratio
├── meta_description (varchar 255, nullable)
├── keywords (text, nullable)
├── lsi_keywords (text, nullable)
├── is_published (boolean, default false)
├── is_auto_post (boolean, default false)
├── is_line_broadcasted (boolean, default false)
├── published_at (bigint, unix timestamp, nullable) — SEO ranking signal
├── notified_at (bigint, unix timestamp, nullable) — auto-post lock
├── view_count (int, nullable)
├── author_user_id (foreign key → users)
├── created_at (bigint, unix timestamp)
├── updated_at (bigint, unix timestamp)
└── Indexes: [is_published, published_at]
```

**Important Note:** As of migration `2026_05_08_070715_convert_dates_to_unix_timestamps_in_articles_and_logs.php`, all timestamp columns were converted to **Unix timestamps (bigint)** instead of standard datetime fields. This has implications for query filtering discussed below.

### 1.2 Related Tables

#### ArticlePlan
```
article_plans
├── id (bigint, primary key)
├── publish_date (date)
├── publish_time (varchar 10)
├── type (varchar, nullable)
├── topic (varchar) — e.g., "วันตรุษจีน", "วันแม่"
├── is_lottery (boolean, default false)
└── timestamps
```

**Purpose:** Stores planned publication dates and topics for the Content Refresh workflow.

#### ArticleComment
```
article_comments
├── id (bigint, primary key)
├── article_id (foreign key → articles)
├── user_id (foreign key → users)
├── comment (text)
├── status (varchar) — APPROVED, PENDING, REJECTED
└── timestamps
```

#### ArticleShare
```
article_shares
├── id (bigint, primary key)
├── article_id (foreign key → articles)
├── platform (varchar) — 'facebook', 'line'
├── shared_at (bigint, unix timestamp)
└── metadata (json, nullable)
```

#### FacebookImportedPost
```
facebook_imported_posts
├── id (bigint, primary key)
├── facebook_post_id (varchar, unique)
├── message (text, nullable)
├── story (text, nullable)
├── full_picture (varchar, nullable)
├── attachments_json (json, nullable)
├── raw_json (json, nullable)
├── facebook_created_time (datetime, nullable)
├── imported_at (datetime)
└── timestamps
```

**Purpose:** Stores imported Facebook posts for the Content Refresh feature to process.

---

## Section 2: Year-Based Data Separation Implementation

### 2.1 Storage Layer (File System Paths)

**Pattern:** `articles/{YEAR}/{SLUG}/{FILENAME}`

**Locations where year-based paths are created:**

#### A. FacebookContentRefreshService (Line 712)
```php
private function storePostImage(FacebookImportedPost $post, string $slug, Carbon $date): ?string
{
    // ...
    $year = $date->copy()->timezone('Asia/Bangkok')->format('Y');
    $path = "articles/{$year}/{$slug}/facebook-cover-{$post->id}.{$ext}";
    Storage::disk('public')->put($path, $response->body());
    return $path;
}
```

#### B. FetchLatestLotteryCommand (Line 228)
```php
private function syncLotteryArticleCover(LotteryResult $result, Carbon $now, bool $wasAlreadyComplete = false): ?Article
{
    // ...
    $pathBase = "articles/{$drawDate->year}/{$article->slug}";
    Storage::disk('public')->put("{$pathBase}/{$squareSvgFilename}", $squareSvg);
    // ... saves images to year-specific directories
}
```

#### C. SyncArticlesFromFacebookByTitleCommand (Similar pattern)
```php
private function storePostImage(FacebookImportedPost $post, string $slug, Carbon $date): ?string
{
    $year = $date->copy()->timezone('Asia/Bangkok')->format('Y');
    $path = "articles/{$year}/{$safeSlug}/facebook-cover-{$post->id}.{$ext}";
    // ...
}
```

**Result:** All article images are stored with year in the path. Example:
- 2025: `articles/2025/new-year-lucky-numbers/facebook-cover-12345.jpg`
- 2026: `articles/2026/new-year-lucky-numbers/facebook-cover-67890.jpg`

### 2.2 Query Layer (Filtering by Year)

**Pattern:** `whereYear(TIMESTAMP_COLUMN, $year)`

#### A. ArticlePlanController::index (Line 18)
```php
public function index(Request $request): JsonResponse
{
    $planYear = (int) $request->query('plan_year', now()->year);
    $planYear = max(2026, min(2037, $planYear));

    $plans = ArticlePlan::query()
        ->whereYear('publish_date', $planYear)
        ->orderBy('publish_date')
        ->orderBy('publish_time')
        ->get();

    return response()->json(['data' => $plans]);
}
```

#### B. ArticleController::index (Lines 41-42)
```php
public function index(Request $request)
{
    $query = Article::query();
    $selectedMonthPlan = $request->query('month_plan'); // e.g., "มกราคม 66" (January 2025)
    
    if ($selectedMonthPlan) {
        // ... parse Thai month format
        $query->whereMonth('published_at', $monthNum)
              ->whereYear('published_at', $yearFull);
    }
    
    return $query->orderByRaw('COALESCE(published_at, created_at) DESC')
        ->paginate(20);
}
```

**Problem:** The `whereYear()` calls assume articles are strictly tied to a single year, but the Content Refresh feature creates scenarios where:
- An article created in 2025 with `published_at = 2025-01-15`
- Gets refreshed in 2026 with new content but **the same article ID and slug**
- The image path still points to `articles/2025/...` even though it's now in 2026

---

## Section 3: Content Refresh/Optimization Feature Analysis

### 3.1 Feature Overview

**Service:** `app/Services/FacebookContentRefreshService`

**Purpose:** Automatically create or update articles by:
1. Fetching Facebook posts from the `facebook_imported_posts` table
2. Matching them to planned topics in `article_plans`
3. Creating or updating an article with the latest Facebook content
4. Preserving the original `published_at` date for SEO authority

**Key Flow:**

```
ArticlePlan records (topics)
    ↓
FacebookImportedPost records (matching by topic search)
    ↓
FacebookContentRefreshService::refresh()
    ├─ Group by topic (e.g., "วันตรุษจีน")
    ├─ Find matching Facebook posts
    ├─ Create/update article with latest content
    ├─ Build article data with combined information
    ├─ **Set published_at = earliest matched import date**
    └─ Delete matched FacebookImportedPost records
```

### 3.2 Article Data Creation (Lines 420-462)

```php
private function buildArticleData(
    string $topicKey,
    FacebookImportedPost $latestImport,
    FacebookImportedPost $earliestImport,
    ?int $authorUserId
): array {
    $meta = $this->topicMetadata($topicKey);
    $latestDate = $this->resolvePostDate($latestImport) ?? Carbon::now('Asia/Bangkok');
    $earliestDate = $this->resolvePostDate($earliestImport) ?? $latestDate;
    $storedImagePath = $this->storePostImage($latestImport, (string) $meta['slug'], $latestDate);

    // Title includes latest year for freshness
    $latestBeYear = $latestDate->copy()->timezone('Asia/Bangkok')->year + 543;
    $title = sprintf('%s %d: %s', $meta['display_name'], $latestBeYear, $meta['title_suffix']);

    // **CRITICAL:** Content reflects latest data, but published_at keeps oldest date
    $articleData = [
        'title' => $title,
        'slug' => $meta['slug'],  // e.g., 'new-year-lucky-numbers' (constant across years)
        'excerpt' => $excerpt,
        'content' => $contentHtml,
        'is_published' => true,
        'published_at' => $earliestDate,  // ← ORIGINAL/EARLIEST DATE PRESERVED
        'author_user_id' => $authorUserId,
    ];

    // Image stored with current year
    if ($storedImagePath !== null) {
        $articleData['cover_image_path'] = $storedImagePath;
        // Path includes: articles/{$latestDate->year}/{$slug}/...
    }

    return $articleData;
}
```

### 3.3 The Problem: Year Mismatch

**Scenario: Annual Topic Refresh Across Years**

**Year 2025 (Initial Article):**
1. Facebook posts about "วันตรุษจีน" (Chinese New Year 2025) are imported
2. `ArticlePlan` records with topic="วันตรุษจีน" and publish_date=2025-02-08 are created
3. `FacebookContentRefreshService::refresh()` runs:
   - Creates article with slug=`chinese-new-year-wealth-numbers`
   - Sets `published_at = 2025-02-08` (for SEO authority)
   - Stores images at: `articles/2025/chinese-new-year-wealth-numbers/...`

**Year 2026 (Refresh):**
4. Facebook posts about "วันตรุษจีน" (Chinese New Year 2026) are imported
5. `ArticlePlan` records with topic="วันตรุษจีน" and publish_date=2026-01-29 are created
6. `FacebookContentRefreshService::refresh()` runs again:
   - **Finds existing article** with same slug
   - **Updates** the article with new content
   - **New images stored at:** `articles/2026/chinese-new-year-wealth-numbers/...`
   - **But `published_at` stays:** 2025-02-08
   - Title becomes: "วันตรุษจีน 2568: เลขรับทรัพย์..." (reflects 2026 in Buddhist year)

**The Breakage:**

```
Article record:
├── slug: 'chinese-new-year-wealth-numbers'
├── published_at: 2025-02-08  ← From 2025 refresh
├── cover_image_path: 'articles/2026/chinese-new-year-wealth-numbers/...'  ← From 2026 refresh
└── content: [2026 Facebook post content]

API Query (ArticleController::index with month_plan="มกราคม 67"):
├── Converts to: year=2026, month=1
├── Filters: WHERE YEAR(published_at)=2026 AND MONTH(published_at)=1
├── Result: ❌ EMPTY (because published_at=2025-02-08)

External Links to old images:
├── Still reference: articles/2025/chinese-new-year-wealth-numbers/...
├── But images now exist at: articles/2026/chinese-new-year-wealth-numbers/...
└── Result: ❌ Broken image links (404 errors)
```

---

## Section 4: Data Retrieval Logic Breakdown

### 4.1 ArticleController::index() - The Main Retrieval Issue

**Location:** `app/Http/Controllers/Api/ArticleController.php`, lines 20-49

```php
public function index(Request $request)
{
    $query = Article::query();

    $selectedMonthPlan = $request->query('month_plan');
    if ($selectedMonthPlan) {
        $thaiMonthsReverse = [
            'มกราคม' => 1, 'กุมภาพันธ์' => 2, // ...
        ];

        $parts = explode(' ', trim($selectedMonthPlan));
        if (count($parts) === 2) {
            $monthName = $parts[0];           // e.g., "มกราคม"
            $yearShort = (int) $parts[1];     // e.g., "66" (2566 Buddhist year = 2023 CE)
            
            if (isset($thaiMonthsReverse[$monthName])) {
                $monthNum = $thaiMonthsReverse[$monthName];
                $yearFull = 2000 + $yearShort - 43;  // Convert Buddhist to CE
                
                // ⚠️ CRITICAL FILTER:
                $query->whereMonth('published_at', $monthNum)
                      ->whereYear('published_at', $yearFull);
            }
        }
    }

    return $query->orderByRaw('COALESCE(published_at, created_at) DESC')
        ->paginate(20);
}
```

**Issue Breakdown:**

1. **Input Example:** User selects `month_plan="มกราคม 67"` (January 2024 Buddhist year)
   - Converts to: `month=1, year=2023` (CE)

2. **Query Executes:** Returns articles where `published_at` month=1 AND year=2023

3. **Problem:** If an article was originally published 2023-01-15 but refreshed in 2025:
   - Original `published_at` still = 2023-01-15 ✅ (query includes it)
   - BUT the `cover_image_path` now points to `articles/2025/...` ⚠️

4. **If an article's `published_at` changes during refresh** (incorrect scenario):
   - Article no longer appears in historical month queries ❌
   - SEO ranking signal is lost ❌

---

## Section 5: The Core Problem Summary

### 5.1 Problem Statement

The **year-based separation** creates a **data mismatch**:

| Aspect | Issue | Impact |
|--------|-------|--------|
| **Image Storage** | Paths include year: `articles/{YEAR}/{SLUG}/...` | Links break when images move to new year |
| **Article Identity** | Slug remains constant across years | Same article in multiple years appears as one |
| **Publication Date** | Preserved as original for SEO | Queries using `whereYear(published_at, year)` may exclude refreshed articles |
| **Retrieval Queries** | Filter by `YEAR(published_at)` | Articles refreshed in new year not found in historical queries |
| **Content Updates** | New content added annually | Title shows new year, but published_at is old |

### 5.2 Why It Breaks

**Assumption Embedded in Code:**
> *"Each article corresponds to a single calendar year. Images are stored in year-specific directories. Queries filter by publication year."*

**Reality of Content Refresh:**
> *"Annual articles (holidays, recurring events) are updated every year while keeping the same slug and original publication date for SEO authority. Images are stored in new year directories, but the article's year-based identity doesn't change."*

---

## Section 6: Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                     CONTENT REFRESH FLOW                             │
└─────────────────────────────────────────────────────────────────────┘

YEAR 2025:
┌──────────────────────────────────────────────────────────────────┐
│ 1. Facebook posts imported (topic: "วันตรุษจีน")                │
│    → facebook_imported_posts table (message, story, attachments)  │
│ 2. ArticlePlan created (topic: "วันตรุษจีน", date: 2025-02-08)  │
│ 3. FacebookContentRefreshService::refresh() triggers:            │
│    a. Match imports by topic keywords                             │
│    b. Create Article:                                             │
│       ├─ slug: 'chinese-new-year-wealth-numbers' (constant)      │
│       ├─ published_at: 2025-02-08 (earliest)                    │
│       ├─ content: [2025 Facebook posts merged]                   │
│       └─ cover_image_path: 'articles/2025/.../{file}.jpg'        │
│    c. Store images at: articles/2025/chinese-new-year-*/...       │
│    d. Delete matched FacebookImportedPost records                 │
└──────────────────────────────────────────────────────────────────┘
                              ↓ (1 year passes)

YEAR 2026:
┌──────────────────────────────────────────────────────────────────┐
│ 4. New Facebook posts imported (same topic)                       │
│ 5. New ArticlePlan created (same topic, date: 2026-01-29)        │
│ 6. FacebookContentRefreshService::refresh() triggers again:       │
│    a. Match new imports by topic                                  │
│    b. Find EXISTING Article with same slug                        │
│    c. Update Article:                                             │
│       ├─ slug: 'chinese-new-year-wealth-numbers' (unchanged)     │
│       ├─ published_at: 2025-02-08 (UNCHANGED for SEO!)           │
│       ├─ content: [2026 Facebook posts merged]                   │
│       └─ cover_image_path: 'articles/2026/.../{file}.jpg'        │
│    d. Store NEW images at: articles/2026/chinese-new-year-*/...   │
│    e. Delete matched FacebookImportedPost records                 │
└──────────────────────────────────────────────────────────────────┘

PROBLEM MANIFESTS:
┌──────────────────────────────────────────────────────────────────┐
│ Article DB State:                                                 │
│ ├─ slug: chinese-new-year-wealth-numbers                         │
│ ├─ published_at: 2025-02-08                                      │
│ ├─ content: [2026 content] ← Fresh!                             │
│ └─ cover_image_path: articles/2026/...  ← New year path!        │
│                                                                   │
│ Query: ArticleController::index(month_plan="มกราคม 68")         │
│ ├─ Converts to: year=2026, month=1                              │
│ ├─ Filters: WHERE YEAR(published_at)=2026 AND MONTH=1           │
│ └─ RESULT: ❌ Empty! (published_at=2025, not 2026)              │
│                                                                   │
│ Image URLs: <img src="articles/2025/...">                       │
│ ├─ Old posts/caches may link to old 2025 images                │
│ ├─ But images now stored at articles/2026/...                   │
│ └─ RESULT: ❌ 404 broken images                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## Section 7: Affected Components

### 7.1 Directly Affected

1. **ArticleController::index()** - Filters by year, misses refreshed articles
2. **FacebookContentRefreshService** - Stores images in year dirs, doesn't update links on refresh
3. **Article image paths** - Point to year-specific directories
4. **Frontend/API consumers** - Get outdated image URLs, missing articles in yearly filters

### 7.2 Indirectly Affected

1. **Analytics** - May not track refreshed article views correctly
2. **SEO** - Multiple year paths for same content confuses crawlers
3. **Caching** - Cache keys may reference old year paths
4. **Migration/Backup** - Year-based organization complicates data portability

---

## Section 8: Three Potential Solutions

### Solution 1: Decouple Image Storage from Year (Recommended)

**Approach:** Store all images with article slug only, not year

#### Implementation Details:

```php
// BEFORE (FacebookContentRefreshService::storePostImage)
$path = "articles/{$year}/{$slug}/facebook-cover-{$post->id}.{$ext}";

// AFTER
$path = "articles/content/{$slug}/facebook-cover-{$post->id}.{$ext}";
// Or for dated updates:
$path = "articles/content/{$slug}/{$latestDate->format('Y-m-d')}/facebook-cover-{$post->id}.{$ext}";
```

#### Pros:
- ✅ Images always point to same directory regardless of year
- ✅ No broken links on refresh
- ✅ Simpler to manage, migrate, or reorganize
- ✅ SEO benefits preserved (URL stability)
- ✅ Minimal query logic changes needed

#### Cons:
- ⚠️ Need migration to move existing images
- ⚠️ Requires database update script for old paths
- ⚠️ Storage disk cleanup script needed for orphaned year dirs

#### Implementation Steps:
1. Create migration to add image path normalization function
2. Batch update all `cover_image_*_path` columns to remove year component
3. Write storage migration script to move files from `articles/YYYY/` to `articles/content/`
4. Update `FacebookContentRefreshService` to use new path pattern
5. Update `FetchLatestLotteryCommand` similarly
6. Test image URLs with new paths
7. Run cleanup to remove empty year directories

---

### Solution 2: Track Article Versions Separately

**Approach:** Create an `ArticleVersion` table to track yearly updates; primary article stays generic

#### Database Changes:

```sql
CREATE TABLE article_versions (
    id BIGINT PRIMARY KEY,
    article_id BIGINT NOT NULL,
    version_year INT NOT NULL,
    published_at BIGINT,
    content LONGTEXT,
    cover_image_path VARCHAR,
    created_at BIGINT,
    FOREIGN KEY (article_id) REFERENCES articles(id),
    UNIQUE (article_id, version_year),
    INDEX (article_id, version_year)
);

-- Modify articles table:
-- Remove published_at, content, cover_image_* (move to versions)
```

#### Logic Changes:

```php
// ArticleController::index() becomes:
$query = Article::with(['versions' => function($q) use ($year) {
    $q->where('version_year', $year);
}]);
// Returns articles with content for specific year

// FacebookContentRefreshService becomes:
$version = ArticleVersion::firstOrCreate(
    ['article_id' => $article->id, 'version_year' => 2026],
    ['content' => $newContent, 'published_at' => 2025, ...]
);
```

#### Pros:
- ✅ Full separation of concerns
- ✅ Preserves all historical versions
- ✅ Clear versioning strategy
- ✅ Supports year-specific analytics
- ✅ Clean query logic

#### Cons:
- ⚠️ Significant schema refactor (high risk)
- ⚠️ Existing queries break (major code changes)
- ⚠️ Migration complex and error-prone
- ⚠️ Requires extensive testing
- ⚠️ Higher complexity for queries

#### Implementation Steps:
1. Create `ArticleVersion` table and backfill data
2. Refactor Article model relationships
3. Update all article retrieval queries
4. Rewrite `FacebookContentRefreshService` logic
5. Update `FetchLatestLotteryCommand` similarly
6. Comprehensive testing of all article workflows
7. Gradual rollout with feature flags

---

### Solution 3: Keep Historical Images, Redirect/Alias Newer Paths

**Approach:** Keep year-based paths for existing articles; new refreshes create aliases

#### Implementation Details:

```php
// Create image path alias registry
CREATE TABLE image_path_aliases (
    id BIGINT PRIMARY KEY,
    article_id BIGINT NOT NULL,
    source_path VARCHAR, -- articles/2025/chinese-new-year/image.jpg
    canonical_path VARCHAR, -- articles/2026/chinese-new-year/image.jpg
    created_at BIGINT,
    FOREIGN KEY (article_id) REFERENCES articles(id)
);

// When refreshing, log the mapping:
ImagePathAlias::create([
    'article_id' => $article->id,
    'source_path' => $oldPath,
    'canonical_path' => $newPath,
]);

// When serving images, resolve through alias:
public function resolveImagePath($article, $imageType = 'square') {
    $storedPath = $article->{"cover_image_{$imageType}_path"};
    $alias = ImagePathAlias::where('article_id', $article->id)
        ->where('source_path', $storedPath)
        ->first();
    return $alias?->canonical_path ?? $storedPath;
}
```

#### Pros:
- ✅ Minimal database schema changes
- ✅ Backward compatible with existing paths
- ✅ No need to migrate existing images
- ✅ Flexible for gradual rollout
- ✅ Can be deployed independently

#### Cons:
- ⚠️ Added query overhead for alias resolution
- ⚠️ Still uses year-based storage (not addressing root cause)
- ⚠️ Complexity in alias management
- ⚠️ Old paths still scattered in codebase

#### Implementation Steps:
1. Create `ImagePathAlias` migration
2. Add alias resolution helper in Article model
3. Update image rendering views to use resolver
4. Modify `FacebookContentRefreshService` to log aliases
5. Create cleanup command to audit orphaned paths
6. Monitor alias usage for optimization

---

## Section 9: Recommended Implementation Path

### Primary Recommendation: **Solution 1** (Decouple Storage)

**Rationale:**
- Simplest to implement with lowest risk
- Directly addresses the root cause (year-based paths)
- Minimal code changes required
- Can be done incrementally
- Preserves all existing functionality

### Secondary Recommendation: **Solution 3** (Alias System)

**Use Case:** If immediate large-scale migration is impossible
- Deploy quickly as interim solution
- Build Solution 1 gradually in background
- Low-risk proof of concept

### Not Recommended: **Solution 2** (Versioning)

**Rationale:**
- Over-engineered for current needs
- High implementation risk
- Requires extensive refactoring
- Can be revisited if multi-version features needed in future

---

## Section 10: Additional Issues & Edge Cases

### 10.1 Unix Timestamp Conversion

The conversion to Unix timestamps (bigint) in migration `2026_05_08_070715` adds complexity:

```php
// Problem: MySQL YEAR() function works on datetime, but columns are now bigint
// This may cause silent failures or unexpected behavior:
$query->whereYear('published_at', 2026);  // May not work as expected with bigint

// Solution: Convert explicitly
$query->whereRaw('FROM_UNIXTIME(published_at, "%Y") = ?', [2026]);
// or
$query->whereRaw('YEAR(FROM_UNIXTIME(published_at)) = ?', [2026]);
// or (SQLite):
$query->whereRaw('strftime("%Y", datetime(published_at, "unixepoch")) = ?', [2026]);
```

**Action Item:** Audit all `whereYear()`, `whereMonth()` calls on timestamp columns.

### 10.2 Content HTML References to Images

Articles with embedded image references:

```html
<!-- Article content (stored as HTML) -->
<img src="/storage/articles/2025/chinese-new-year-wealth-numbers/image.jpg" alt="..." />

<!-- On refresh, images move to 2026, but embedded HTML still references 2025 -->
<!-- Result: Broken images in article content -->
```

**Solution:** Content sanitizer/updater needed when refreshing articles.

### 10.3 API Clients & External Links

Any API client caching or external sites linking to articles:

```
Old URL: /articles/chinese-new-year-wealth-numbers?v=2025
New content serves 2026 data but old links still work ✅

Old image URLs: /storage/articles/2025/...
New images at: /storage/articles/2026/...
External links break ❌
```

---

## Section 11: Testing Strategy

### 11.1 Unit Tests Needed

1. **Test year-based image path generation**
   - Verify paths are created correctly
   - Test with different years

2. **Test article retrieval by month/year**
   - Verify `whereYear()` and `whereMonth()` work with unix timestamps
   - Test filtering across year boundaries

3. **Test content refresh logic**
   - Create article in 2025, refresh in 2026
   - Verify `published_at` preserved
   - Verify image paths updated correctly

### 11.2 Integration Tests Needed

1. **Full refresh cycle**
   - Import Facebook posts
   - Create ArticlePlan
   - Run refresh
   - Query articles
   - Verify images accessible

2. **Historical article queries**
   - Query by month/year
   - Verify refreshed articles appear in correct results

3. **Cross-year scenarios**
   - Article created 2025, refreshed 2026, queried for 2026
   - Article created 2025, queried for 2025 after 2026 refresh

---

## Section 12: Risk Assessment

| Issue | Severity | Likelihood | Impact | Mitigation |
|-------|----------|------------|--------|-----------|
| Broken article links | HIGH | HIGH | Articles unfindable in yearly views | Implement Solution 1 |
| Broken image URLs | HIGH | HIGH | Content display broken | Image path decouple |
| SEO ranking loss | HIGH | MEDIUM | Search visibility reduced | Preserve published_at |
| Cache invalidation | MEDIUM | MEDIUM | Old versions served | Add cache busting |
| Data migration errors | MEDIUM | HIGH | Orphaned files, DB inconsistencies | Comprehensive testing |
| Query performance | LOW | MEDIUM | Slower lookups if not optimized | Add indexes |

---

## Conclusion

The **year-based separation** in Supernumber's Content Refresh feature creates systematic data mismatches between article metadata, image storage, and retrieval queries. The root cause is an architectural assumption that articles map 1:1 to calendar years, which breaks when annual topics are refreshed.

**Recommended action:** Implement **Solution 1** (decouple image storage from year) as it directly addresses the root cause with minimal risk and code changes.

The implementation roadmap should prioritize:
1. Image path decoupling
2. Unix timestamp query fixes  
3. Comprehensive testing
4. Gradual rollout with monitoring

This analysis provides the foundation for implementation planning and risk mitigation.

---

**Document Prepared For:** Technical Review  
**Scope:** Database schema, Content Refresh feature, year-based data organization  
**Next Steps:** Implementation planning, detailed coding estimates, testing strategy refinement
