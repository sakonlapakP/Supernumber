import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../models/article.model.dart';
import '../models/article_plan.model.dart';
import '../providers/article_plan_provider.dart';
import '../providers/auth_provider.dart';

// ─────────────────────────────────────────────────────────
//  Constants
// ─────────────────────────────────────────────────────────

const _kStatusLabels = {
  'todo': '⭕ วางแผน',
  'in_progress': '📝 กำลังทำ',
  'done': '✅ เสร็จ',
  'blocked': '🔴 ติดขัด',
  'cancelled': '❌ ยกเลิก',
  'overdue': '⚠️ เลยกำหนด',
};

const _kStatusColors = {
  'todo': Color(0xFF64748B),
  'in_progress': Color(0xFF3B82F6),
  'done': Color(0xFF10B981),
  'blocked': Color(0xFFEF4444),
  'cancelled': Color(0xFF94A3B8),
  'overdue': Color(0xFFB91C1C),
};

const _kStatusBg = {
  'todo': Color(0xFFF1F5F9),
  'in_progress': Color(0xFFEFF6FF),
  'done': Color(0xFFF0FDF4),
  'blocked': Color(0xFFFEF2F2),
  'cancelled': Color(0xFFF8FAFC),
  'overdue': Color(0xFFFEF2F2),
};

const _kTypeColors = {
  'หวย': Color(0xFFEF4444),
  'วันสำคัญ': Color(0xFF3B82F6),
  'วันมู': Color(0xFF9333EA),
  'evergreen': Color(0xFF16A34A),
  'บทวิเคราะห์': Color(0xFFF59E0B),
};

const _kTypeBg = {
  'หวย': Color(0xFFFEF2F2),
  'วันสำคัญ': Color(0xFFEFF6FF),
  'วันมู': Color(0xFFFAF5FF),
  'evergreen': Color(0xFFF0FDF4),
  'บทวิเคราะห์': Color(0xFFFFFBEB),
};

const _kTypeOptions = ['วันสำคัญ', 'หวย', 'Evergreen', 'วันมู', 'บทวิเคราะห์'];
const _kStatusOptions = ['todo', 'in_progress', 'done', 'blocked', 'cancelled'];

const _thaiMonthsFull = [
  '',
  'มกราคม',
  'กุมภาพันธ์',
  'มีนาคม',
  'เมษายน',
  'พฤษภาคม',
  'มิถุนายน',
  'กรกฎาคม',
  'สิงหาคม',
  'กันยายน',
  'ตุลาคม',
  'พฤศจิกายน',
  'ธันวาคม',
];

const _thaiWeekdayShort = ['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา'];
const _thaiWeekdayFull = [
  'จันทร์',
  'อังคาร',
  'พุธ',
  'พฤหัส',
  'ศุกร์',
  'เสาร์',
  'อาทิตย์',
];

// ─────────────────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────────────────

String _effectiveStatus(ArticlePlan item, List<Article> articles) {
  final planDateStr = DateFormat('yyyy-MM-dd').format(item.publishDate);

  // Check matching published article
  for (final a in articles) {
    if (a.publishedAt != null &&
        DateFormat('yyyy-MM-dd').format(a.publishedAt!) == planDateStr) {
      return 'done';
    }
    if (item.isLottery && a.slug != null) {
      final monthStr = item.publishDate.month.toString().padLeft(2, '0');
      final isRound1 = item.publishDate.day <= 15;
      final pattern =
          'thai-goverment-lottery-${item.publishDate.year}$monthStr${isRound1 ? 'first' : 'second'}';
      if (a.slug == pattern) return 'done';
    }
  }

  if (item.status == 'done' ||
      item.status == 'cancelled' ||
      item.status == 'blocked' ||
      item.status == 'in_progress') {
    return item.status!;
  }

  final today = DateTime(
      DateTime.now().year, DateTime.now().month, DateTime.now().day);
  if (item.publishDate.isBefore(today)) return 'overdue';

  return item.status ?? 'todo';
}

DateTime _mondayOf(DateTime date) {
  final d = DateTime(date.year, date.month, date.day);
  return d.subtract(Duration(days: d.weekday - 1));
}

String _fmtTime(TimeOfDay t) =>
    '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';

TimeOfDay? _parseTime(String? value) {
  if (value == null || value.isEmpty) return null;
  final parts = value.split(':');
  if (parts.length < 2) return null;
  final h = int.tryParse(parts[0]);
  final m = int.tryParse(parts[1]);
  if (h == null || m == null) return null;
  return TimeOfDay(hour: h.clamp(0, 23), minute: m.clamp(0, 59));
}

// ─────────────────────────────────────────────────────────
//  Main widget
// ─────────────────────────────────────────────────────────

class ContentPlanSection extends StatefulWidget {
  final String? selectedMonth;
  final int selectedYear;
  final List<Article> articles;
  final ValueChanged<int> onYearChanged;

  const ContentPlanSection({
    super.key,
    required this.selectedMonth,
    required this.selectedYear,
    required this.articles,
    required this.onYearChanged,
  });

  @override
  State<ContentPlanSection> createState() => _ContentPlanSectionState();
}

class _ContentPlanSectionState extends State<ContentPlanSection>
    with SingleTickerProviderStateMixin {
  late TabController _tabController2; // iPad: ปฏิทิน/ไทม์ไลน์ | ตาราง
  late TabController _tabController3; // iPhone: ปฏิทิน | Timeline | ตาราง
  late DateTime _calendarMonth;
  late DateTime _timelineWeek;
  String? _statusFilter;
  String? _typeFilter;

  @override
  void initState() {
    super.initState();
    _tabController2 = TabController(length: 2, vsync: this);
    _tabController2.addListener(() => setState(() {}));
    _tabController3 = TabController(length: 3, vsync: this);
    _tabController3.addListener(() => setState(() {}));
    final now = DateTime.now();
    _calendarMonth = DateTime(now.year, now.month);
    _timelineWeek = _mondayOf(now);
  }

  @override
  void dispose() {
    _tabController2.dispose();
    _tabController3.dispose();
    super.dispose();
  }

  // ── Header ─────────────────────────────────────────────

  Widget _buildHeader(
      BuildContext context, ArticlePlanProvider planProvider, AuthProvider auth) {
    final totalPlanned = planProvider.plans.length;
    final totalDone = planProvider.plans
        .where((p) => _effectiveStatus(p, widget.articles) == 'done')
        .length;

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: const Color(0xFF7C3AED).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.calendar_month_rounded,
                color: Color(0xFF7C3AED), size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              'CONTENT ROADMAP',
              style:
                  GoogleFonts.kanit(fontSize: 18, fontWeight: FontWeight.bold),
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(99),
            ),
            child: Text(
              'เตรียมแล้ว $totalDone / $totalPlanned',
              style: GoogleFonts.kanit(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: const Color(0xFF475569)),
            ),
          ),
          const SizedBox(width: 6),
          DropdownButtonHideUnderline(
            child: DropdownButton<int>(
              value: widget.selectedYear,
              isDense: true,
              style: GoogleFonts.kanit(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: const Color(0xFF7C3AED)),
              items: List.generate(12, (i) => 2026 + i)
                  .map((y) => DropdownMenuItem(
                        value: y,
                        child: Text('ปี ${(y + 543).toString().substring(2)}'),
                      ))
                  .toList(),
              onChanged: (v) {
                if (v == null) return;
                widget.onYearChanged(v);
              },
            ),
          ),
          if (auth.user?['role'] == 'manager')
            IconButton(
              onPressed: () => _openPlanDialog(context, widget.selectedYear),
              icon: const Icon(Icons.add_circle_outline,
                  color: Color(0xFF7C3AED)),
              tooltip: 'เพิ่มแผนงาน',
              padding: const EdgeInsets.all(4),
              constraints: const BoxConstraints(),
            ),
        ],
      ),
    );
  }

  // ── Calendar View ──────────────────────────────────────

  Widget _buildCalendarView(BuildContext context, List<ArticlePlan> plans) {
    final year = _calendarMonth.year;
    final month = _calendarMonth.month;
    final thaiYear = year + 543;

    final plansByDay = <int, List<ArticlePlan>>{};
    for (final p in plans) {
      if (p.publishDate.year == year && p.publishDate.month == month) {
        plansByDay.putIfAbsent(p.publishDate.day, () => []).add(p);
      }
    }

    final firstWeekday = DateTime(year, month, 1).weekday;
    final daysInMonth = DateTime(year, month + 1, 0).day;
    final offset = firstWeekday - 1;
    final today = DateTime.now();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            IconButton(
              icon: const Icon(Icons.chevron_left),
              onPressed: () => setState(() {
                _calendarMonth =
                    DateTime(_calendarMonth.year, _calendarMonth.month - 1);
              }),
            ),
            Expanded(
              child: Center(
                child: Text(
                  '${_thaiMonthsFull[month]} $thaiYear',
                  style: GoogleFonts.kanit(
                      fontWeight: FontWeight.bold, fontSize: 16),
                ),
              ),
            ),
            IconButton(
              icon: const Icon(Icons.chevron_right),
              onPressed: () => setState(() {
                _calendarMonth =
                    DateTime(_calendarMonth.year, _calendarMonth.month + 1);
              }),
            ),
            TextButton(
              onPressed: () => setState(() {
                _calendarMonth =
                    DateTime(DateTime.now().year, DateTime.now().month);
              }),
              child: Text('วันนี้',
                  style: GoogleFonts.kanit(color: const Color(0xFF7C3AED))),
            ),
          ],
        ),
        const SizedBox(height: 4),
        // Weekday headers
        Row(
          children: List.generate(7, (i) {
            final label = _thaiWeekdayShort[i];
            final color = label == 'อา'
                ? const Color(0xFFEF4444)
                : label == 'ส'
                    ? const Color(0xFF3B82F6)
                    : const Color(0xFF94A3B8);
            return Expanded(
              child: Center(
                child: Text(label,
                    style: GoogleFonts.kanit(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: color)),
              ),
            );
          }),
        ),
        const SizedBox(height: 4),
        // Days grid — aspect ratio computed from cell width so height stays ~58px on all screens
        LayoutBuilder(
          builder: (context, constraints) {
            final cellWidth = constraints.maxWidth / 7;
            final aspectRatio = cellWidth / 58.0;
            return GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 7,
            childAspectRatio: aspectRatio,
          ),
          itemCount: offset + daysInMonth,
          itemBuilder: (context, index) {
            if (index < offset) return const SizedBox();
            final day = index - offset + 1;
            final dayPlans = plansByDay[day] ?? [];
            final isToday = today.year == year &&
                today.month == month &&
                today.day == day;

            return GestureDetector(
              onTap: dayPlans.isEmpty
                  ? null
                  : () => _showDayPlans(
                      context, DateTime(year, month, day), dayPlans),
              child: Container(
                margin: const EdgeInsets.all(2),
                decoration: BoxDecoration(
                  color: isToday
                      ? const Color(0xFF7C3AED)
                      : dayPlans.isNotEmpty
                          ? const Color(0xFFF3E8FF)
                          : null,
                  borderRadius: BorderRadius.circular(8),
                  border: isToday
                      ? null
                      : dayPlans.isNotEmpty
                          ? Border.all(
                              color: const Color(0xFF7C3AED)
                                  .withValues(alpha: 0.3))
                          : null,
                ),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      '$day',
                      style: GoogleFonts.kanit(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: isToday
                            ? Colors.white
                            : dayPlans.isNotEmpty
                                ? const Color(0xFF7C3AED)
                                : const Color(0xFF334155),
                      ),
                    ),
                    if (dayPlans.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Wrap(
                        alignment: WrapAlignment.center,
                        spacing: 2,
                        children: dayPlans.take(3).map((p) {
                          final st = _effectiveStatus(p, widget.articles);
                          return Container(
                            width: 6,
                            height: 6,
                            decoration: BoxDecoration(
                              color: isToday
                                  ? Colors.white
                                  : (_kStatusColors[st] ??
                                      const Color(0xFF7C3AED)),
                              shape: BoxShape.circle,
                            ),
                          );
                        }).toList(),
                      ),
                    ],
                  ],
                ),
              ),
            );
          },
        );
          },
        ),
        const SizedBox(height: 6),
        Text(
          '💡 แตะวันที่มีแผนเพื่อดูรายละเอียด',
          style: GoogleFonts.kanit(fontSize: 11, color: const Color(0xFF94A3B8)),
        ),
      ],
    );
  }

  void _showDayPlans(
      BuildContext context, DateTime date, List<ArticlePlan> dayPlans) {
    final auth = context.read<AuthProvider>();
    final isManager = auth.user?['role'] == 'manager';
    final thaiDate =
        '${date.day} ${_thaiMonthsFull[date.month]} ${date.year + 543}';

    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.5,
        minChildSize: 0.3,
        maxChildSize: 0.85,
        expand: false,
        builder: (ctx, scrollCtrl) => Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 4, 12, 8),
              child: Row(
                children: [
                  Text(thaiDate,
                      style: GoogleFonts.kanit(
                          fontSize: 16, fontWeight: FontWeight.bold)),
                  const Spacer(),
                  if (isManager)
                    TextButton.icon(
                      onPressed: () {
                        Navigator.pop(ctx);
                        _openPlanDialog(context, widget.selectedYear,
                            presetDate: date);
                      },
                      icon: const Icon(Icons.add, size: 16),
                      label: Text('เพิ่มแผน',
                          style: GoogleFonts.kanit(fontSize: 13)),
                    ),
                ],
              ),
            ),
            Expanded(
              child: ListView.separated(
                controller: scrollCtrl,
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                itemCount: dayPlans.length,
                separatorBuilder: (context, index) => const SizedBox(height: 8),
                itemBuilder: (_, i) => _PlanTile(
                  plan: dayPlans[i],
                  articles: widget.articles,
                  year: widget.selectedYear,
                  isManager: isManager,
                  onEdit: (p) {
                    Navigator.pop(ctx);
                    _openPlanDialog(context, widget.selectedYear, plan: p);
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ── Timeline View (weekly) ─────────────────────────────

  Widget _buildTimelineView(BuildContext context, List<ArticlePlan> plans) {
    final weekEnd = _timelineWeek.add(const Duration(days: 6));
    final auth = context.read<AuthProvider>();
    final isManager = auth.user?['role'] == 'manager';

    final plansByDate = <String, List<ArticlePlan>>{};
    for (final p in plans) {
      final key = DateFormat('yyyy-MM-dd').format(p.publishDate);
      plansByDate.putIfAbsent(key, () => []).add(p);
    }

    final todayStr = DateFormat('yyyy-MM-dd').format(DateTime.now());

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            IconButton(
              icon: const Icon(Icons.chevron_left),
              onPressed: () => setState(() {
                _timelineWeek =
                    _timelineWeek.subtract(const Duration(days: 7));
              }),
            ),
            Expanded(
              child: Center(
                child: Text(
                  '${_timelineWeek.day} ${_thaiMonthsFull[_timelineWeek.month]} — '
                  '${weekEnd.day} ${_thaiMonthsFull[weekEnd.month]} ${weekEnd.year + 543}',
                  style: GoogleFonts.kanit(
                      fontWeight: FontWeight.bold, fontSize: 13),
                  textAlign: TextAlign.center,
                ),
              ),
            ),
            IconButton(
              icon: const Icon(Icons.chevron_right),
              onPressed: () => setState(() {
                _timelineWeek = _timelineWeek.add(const Duration(days: 7));
              }),
            ),
            TextButton(
              onPressed: () =>
                  setState(() => _timelineWeek = _mondayOf(DateTime.now())),
              child: Text('สัปดาห์นี้',
                  style:
                      GoogleFonts.kanit(color: const Color(0xFF7C3AED))),
            ),
          ],
        ),
        const SizedBox(height: 4),
        ...List.generate(7, (i) {
          final day = _timelineWeek.add(Duration(days: i));
          final dayStr = DateFormat('yyyy-MM-dd').format(day);
          final dayPlans = plansByDate[dayStr] ?? [];
          final isToday = dayStr == todayStr;

          return Container(
            margin: const EdgeInsets.only(bottom: 8),
            decoration: BoxDecoration(
              color: isToday
                  ? const Color(0xFFF3E8FF)
                  : Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: isToday
                    ? const Color(0xFF7C3AED).withValues(alpha: 0.4)
                    : const Color(0xFFE2E8F0),
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(12, 10, 12, 6),
                  child: Row(
                    children: [
                      Container(
                        width: 32,
                        height: 32,
                        decoration: BoxDecoration(
                          color: isToday
                              ? const Color(0xFF7C3AED)
                              : const Color(0xFFF1F5F9),
                          shape: BoxShape.circle,
                        ),
                        alignment: Alignment.center,
                        child: Text(
                          '${day.day}',
                          style: GoogleFonts.kanit(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: isToday
                                ? Colors.white
                                : const Color(0xFF334155),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        _thaiWeekdayFull[i],
                        style: GoogleFonts.kanit(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: isToday
                              ? const Color(0xFF7C3AED)
                              : const Color(0xFF64748B),
                        ),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        _thaiMonthsFull[day.month],
                        style: GoogleFonts.kanit(
                            fontSize: 12, color: const Color(0xFF94A3B8)),
                      ),
                      const Spacer(),
                      if (dayPlans.isEmpty)
                        Text('ไม่มีแผน',
                            style: GoogleFonts.kanit(
                                fontSize: 11,
                                color: const Color(0xFFCBD5E1))),
                      if (isManager)
                        GestureDetector(
                          onTap: () => _openPlanDialog(
                              context, widget.selectedYear,
                              presetDate: day),
                          child: const Padding(
                            padding: EdgeInsets.only(left: 8),
                            child: Icon(Icons.add_circle_outline,
                                size: 18, color: Color(0xFF7C3AED)),
                          ),
                        ),
                    ],
                  ),
                ),
                if (dayPlans.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(8, 0, 8, 8),
                    child: Column(
                      children: dayPlans
                          .map((p) => _PlanTile(
                                plan: p,
                                articles: widget.articles,
                                year: widget.selectedYear,
                                isManager: isManager,
                                compact: true,
                                onEdit: (plan) => _openPlanDialog(
                                    context, widget.selectedYear,
                                    plan: plan),
                              ))
                          .toList(),
                    ),
                  ),
              ],
            ),
          );
        }),
        const SizedBox(height: 4),
        Text(
          '💡 ← → เพื่อเลื่อนสัปดาห์',
          style: GoogleFonts.kanit(fontSize: 11, color: const Color(0xFF94A3B8)),
        ),
      ],
    );
  }

  // ── Table View ─────────────────────────────────────────

  Widget _buildTableView(
      BuildContext context, List<ArticlePlan> plans, bool isManager) {
    final filtered = plans.where((p) {
      if (_statusFilter != null &&
          _effectiveStatus(p, widget.articles) != _statusFilter) {
        return false;
      }
      if (_typeFilter != null) {
        final t = (p.type ?? '').toLowerCase();
        final f = _typeFilter!.toLowerCase();
        if (t != f) return false;
      }
      return true;
    }).toList();

    final grouped = <String, List<ArticlePlan>>{};
    for (final p in filtered) {
      final key =
          '${_thaiMonthsFull[p.publishDate.month]} ${(p.publishDate.year + 543).toString().substring(2)}';
      grouped.putIfAbsent(key, () => []).add(p);
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Status filter row
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: Row(
            children: [
              _PlanFilterChip(
                label: 'ทุกสถานะ',
                selected: _statusFilter == null,
                onTap: () => setState(() => _statusFilter = null),
              ),
              const SizedBox(width: 6),
              ..._kStatusOptions.map((s) => Padding(
                    padding: const EdgeInsets.only(right: 6),
                    child: _PlanFilterChip(
                      label: _kStatusLabels[s]!,
                      selected: _statusFilter == s,
                      color: _kStatusColors[s],
                      onTap: () => setState(() =>
                          _statusFilter = _statusFilter == s ? null : s),
                    ),
                  )),
            ],
          ),
        ),
        const SizedBox(height: 8),
        // Type filter row
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: Row(
            children: [
              _PlanFilterChip(
                label: 'ทุกประเภท',
                selected: _typeFilter == null,
                onTap: () => setState(() => _typeFilter = null),
              ),
              const SizedBox(width: 6),
              ..._kTypeOptions.map((t) => Padding(
                    padding: const EdgeInsets.only(right: 6),
                    child: _PlanFilterChip(
                      label: t,
                      selected: _typeFilter == t,
                      color: _kTypeColors[t.toLowerCase()],
                      onTap: () => setState(
                          () => _typeFilter = _typeFilter == t ? null : t),
                    ),
                  )),
            ],
          ),
        ),
        const SizedBox(height: 12),
        if (filtered.isEmpty)
          Center(
            child: Padding(
              padding: const EdgeInsets.all(32),
              child: Text('ไม่พบแผนงาน',
                  style: GoogleFonts.kanit(color: const Color(0xFF94A3B8))),
            ),
          )
        else
          ...grouped.entries.map((entry) => Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: double.infinity,
                    padding:
                        const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    margin: const EdgeInsets.only(bottom: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      entry.key,
                      style: GoogleFonts.kanit(
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                          color: const Color(0xFF475569)),
                    ),
                  ),
                  ...entry.value.map((p) => _PlanTile(
                        plan: p,
                        articles: widget.articles,
                        year: widget.selectedYear,
                        isManager: isManager,
                        onEdit: (plan) => _openPlanDialog(
                            context, widget.selectedYear,
                            plan: plan),
                      )),
                  const SizedBox(height: 8),
                ],
              )),
      ],
    );
  }

  // ── Build ───────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final isWide = MediaQuery.of(context).size.width > 600;
    final tabCtrl = isWide ? _tabController2 : _tabController3;

    return Consumer2<ArticlePlanProvider, AuthProvider>(
      builder: (context, planProvider, auth, _) {
        final isManager = auth.user?['role'] == 'manager';
        final plans = planProvider.plans;

        return Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: const Color(0xFFE5EAF2)),
          ),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildHeader(context, planProvider, auth),
              // Tab bar
              Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: TabBar(
                  controller: tabCtrl,
                  indicatorSize: TabBarIndicatorSize.tab,
                  dividerColor: Colors.transparent,
                  indicator: BoxDecoration(
                    color: const Color(0xFF7C3AED),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  labelColor: Colors.white,
                  unselectedLabelColor: const Color(0xFF64748B),
                  labelStyle: GoogleFonts.kanit(
                      fontSize: 12, fontWeight: FontWeight.w600),
                  tabs: isWide
                      ? const [
                          Tab(
                            icon: Icon(Icons.calendar_month_rounded, size: 15),
                            text: 'ปฏิทิน / ไทม์ไลน์',
                            iconMargin: EdgeInsets.only(bottom: 2),
                          ),
                          Tab(
                            icon: Icon(Icons.list_alt_rounded, size: 15),
                            text: 'ตาราง',
                            iconMargin: EdgeInsets.only(bottom: 2),
                          ),
                        ]
                      : const [
                          Tab(
                            icon: Icon(Icons.calendar_month_rounded, size: 15),
                            text: 'ปฏิทิน',
                            iconMargin: EdgeInsets.only(bottom: 2),
                          ),
                          Tab(
                            icon: Icon(Icons.view_week_rounded, size: 15),
                            text: 'Timeline',
                            iconMargin: EdgeInsets.only(bottom: 2),
                          ),
                          Tab(
                            icon: Icon(Icons.list_alt_rounded, size: 15),
                            text: 'ตาราง',
                            iconMargin: EdgeInsets.only(bottom: 2),
                          ),
                        ],
                ),
              ),
              const SizedBox(height: 16),
              if (planProvider.isLoading)
                const Center(child: CircularProgressIndicator())
              else if (isWide) ...[
                if (tabCtrl.index == 0)
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(child: _buildCalendarView(context, plans)),
                      const SizedBox(width: 16),
                      Container(width: 1, color: const Color(0xFFE2E8F0)),
                      const SizedBox(width: 16),
                      Expanded(child: _buildTimelineView(context, plans)),
                    ],
                  )
                else
                  _buildTableView(context, plans, isManager),
              ] else ...[
                if (tabCtrl.index == 0)
                  _buildCalendarView(context, plans)
                else if (tabCtrl.index == 1)
                  _buildTimelineView(context, plans)
                else
                  _buildTableView(context, plans, isManager),
              ],
            ],
          ),
        );
      },
    );
  }

  // ── Form dialog ─────────────────────────────────────────

  Future<void> _openPlanDialog(
    BuildContext context,
    int year, {
    ArticlePlan? plan,
    DateTime? presetDate,
  }) async {
    DateTime selectedDate =
        plan?.publishDate ?? presetDate ?? DateTime.now();
    TimeOfDay selectedTime =
        _parseTime(plan?.publishTime) ?? const TimeOfDay(hour: 9, minute: 0);
    final dateCtrl = TextEditingController(
        text: DateFormat('yyyy-MM-dd').format(selectedDate));
    final timeCtrl = TextEditingController(text: _fmtTime(selectedTime));
    String? selectedType = plan?.type;
    final topicCtrl = TextEditingController(text: plan?.topic ?? '');
    bool isLottery = plan?.isLottery ?? false;
    String selectedStatus = plan?.status ?? 'todo';

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setS) => AlertDialog(
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          title: Text(
              plan == null ? 'เพิ่มแผนการเผยแพร่' : 'แก้ไขแผนการเผยแพร่',
              style: GoogleFonts.kanit(fontWeight: FontWeight.bold)),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: dateCtrl,
                  readOnly: true,
                  decoration: InputDecoration(
                      labelText: 'วันที่',
                      labelStyle: GoogleFonts.kanit(),
                      prefixIcon:
                          const Icon(Icons.calendar_today_rounded, size: 18)),
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: ctx,
                      initialDate: selectedDate,
                      firstDate: DateTime(2026, 1, 1),
                      lastDate: DateTime(2037, 12, 31),
                    );
                    if (picked == null) return;
                    selectedDate = picked;
                    dateCtrl.text = DateFormat('yyyy-MM-dd').format(picked);
                  },
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: timeCtrl,
                  readOnly: true,
                  decoration: InputDecoration(
                      labelText: 'เวลา',
                      labelStyle: GoogleFonts.kanit(),
                      prefixIcon:
                          const Icon(Icons.access_time_rounded, size: 18)),
                  onTap: () async {
                    final picked = await showTimePicker(
                        context: ctx, initialTime: selectedTime);
                    if (picked == null) return;
                    selectedTime = picked;
                    timeCtrl.text = _fmtTime(picked);
                  },
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  value: _kTypeOptions.contains(selectedType) // ignore: deprecated_member_use
                      ? selectedType
                      : null,
                  decoration: InputDecoration(
                      labelText: 'ประเภท',
                      labelStyle: GoogleFonts.kanit(),
                      prefixIcon:
                          const Icon(Icons.label_outline_rounded, size: 18)),
                  style: GoogleFonts.kanit(
                      color: const Color(0xFF1E293B), fontSize: 14),
                  items: [
                    DropdownMenuItem(
                        value: null,
                        child: Text('— ไม่ระบุ —', style: GoogleFonts.kanit())),
                    ..._kTypeOptions.map((t) => DropdownMenuItem(
                        value: t,
                        child: Text(t, style: GoogleFonts.kanit()))),
                  ],
                  onChanged: (v) => setS(() => selectedType = v),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  value: selectedStatus, // ignore: deprecated_member_use
                  decoration: InputDecoration(
                      labelText: 'สถานะ',
                      labelStyle: GoogleFonts.kanit(),
                      prefixIcon: const Icon(Icons.flag_outlined, size: 18)),
                  style: GoogleFonts.kanit(
                      color: const Color(0xFF1E293B), fontSize: 14),
                  items: _kStatusOptions
                      .map((s) => DropdownMenuItem(
                          value: s,
                          child: Text(_kStatusLabels[s]!,
                              style: GoogleFonts.kanit())))
                      .toList(),
                  onChanged: (v) => setS(() => selectedStatus = v ?? 'todo'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: topicCtrl,
                  maxLines: 2,
                  decoration: InputDecoration(
                      labelText: 'หัวข้อ',
                      labelStyle: GoogleFonts.kanit(),
                      prefixIcon: const Icon(Icons.title_rounded, size: 18)),
                ),
                const SizedBox(height: 4),
                SwitchListTile(
                  value: isLottery,
                  onChanged: (v) => setS(() => isLottery = v),
                  title: Text('เป็นบทความหวย', style: GoogleFonts.kanit()),
                  activeThumbColor: const Color(0xFF7C3AED),
                  contentPadding: EdgeInsets.zero,
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: Text('ยกเลิก', style: GoogleFonts.kanit()),
            ),
            FilledButton(
              style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFF7C3AED)),
              onPressed: () => Navigator.pop(ctx, true),
              child: Text('บันทึก',
                  style: GoogleFonts.kanit(fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );

    if (ok != true || !context.mounted) return;
    if (topicCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(context)
          .showSnackBar(const SnackBar(content: Text('กรุณากรอกหัวข้อ')));
      return;
    }

    final payload = {
      'publish_date': dateCtrl.text.trim(),
      'publish_time': timeCtrl.text.trim(),
      'type': selectedType ?? '',
      'topic': topicCtrl.text.trim(),
      'is_lottery': isLottery,
      'status': selectedStatus,
    };
    final p = context.read<ArticlePlanProvider>();
    final success = plan == null
        ? await p.createPlan(payload, year)
        : await p.updatePlan(plan.id, payload, year);
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(
              success ? 'บันทึกสำเร็จ' : (p.lastErrorMessage ?? 'ไม่สำเร็จ'))));
    }
  }
}

// ─────────────────────────────────────────────────────────
//  _PlanTile
// ─────────────────────────────────────────────────────────

class _PlanTile extends StatelessWidget {
  final ArticlePlan plan;
  final List<Article> articles;
  final int year;
  final bool isManager;
  final ValueChanged<ArticlePlan>? onEdit;
  final bool compact;

  const _PlanTile({
    required this.plan,
    required this.articles,
    required this.year,
    required this.isManager,
    this.onEdit,
    this.compact = false,
  });

  @override
  Widget build(BuildContext context) {
    final status = _effectiveStatus(plan, articles);
    final statusColor = _kStatusColors[status] ?? const Color(0xFF64748B);
    final statusBg = _kStatusBg[status] ?? const Color(0xFFF1F5F9);
    final typeKey = (plan.type ?? '').toLowerCase();
    final typeColor = _kTypeColors[typeKey];
    final typeBg = _kTypeBg[typeKey];
    final dateStr =
        '${plan.publishDate.day} ${_thaiMonthsFull[plan.publishDate.month]} | ${plan.publishTime}';

    final rowBg = status == 'done'
        ? const Color(0xFFF0FDF4)
        : status == 'overdue' || status == 'blocked'
            ? const Color(0xFFFEF2F2)
            : Colors.white;
    final rowBorder = status == 'done'
        ? const Color(0xFFBBF7D0)
        : status == 'overdue' || status == 'blocked'
            ? const Color(0xFFFECACA)
            : const Color(0xFFE2E8F0);

    return Container(
      margin: EdgeInsets.only(bottom: compact ? 4 : 6),
      padding: EdgeInsets.all(compact ? 8 : 12),
      decoration: BoxDecoration(
        color: rowBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: rowBorder),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  plan.topic,
                  style: GoogleFonts.kanit(
                    fontSize: compact ? 13 : 14,
                    fontWeight: FontWeight.w600,
                    color: const Color(0xFF1E293B),
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Wrap(
                  spacing: 6,
                  runSpacing: 4,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: [
                    Text(
                      dateStr,
                      style: GoogleFonts.kanit(
                          fontSize: 11, color: const Color(0xFF64748B)),
                    ),
                    if (plan.type != null && plan.type!.isNotEmpty)
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 6, vertical: 1),
                        decoration: BoxDecoration(
                          color: typeBg ?? const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          plan.type!,
                          style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color:
                                  typeColor ?? const Color(0xFF64748B)),
                        ),
                      ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 6, vertical: 1),
                      decoration: BoxDecoration(
                        color: statusBg,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        _kStatusLabels[status] ?? status,
                        style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                            color: statusColor),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          if (isManager) ...[
            const SizedBox(width: 8),
            Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                GestureDetector(
                  onTap: () => onEdit?.call(plan),
                  child: const Icon(Icons.edit_outlined,
                      size: 18, color: Color(0xFF7C3AED)),
                ),
                const SizedBox(height: 10),
                GestureDetector(
                  onTap: () async {
                    await context
                        .read<ArticlePlanProvider>()
                        .deletePlan(plan.id, year);
                  },
                  child: const Icon(Icons.delete_outline,
                      size: 18, color: Color(0xFFC54B3D)),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────
//  _PlanFilterChip
// ─────────────────────────────────────────────────────────

class _PlanFilterChip extends StatelessWidget {
  final String label;
  final bool selected;
  final Color? color;
  final VoidCallback onTap;

  const _PlanFilterChip({
    required this.label,
    required this.selected,
    required this.onTap,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    final activeColor = color ?? const Color(0xFF7C3AED);
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: selected
              ? activeColor.withValues(alpha: 0.12)
              : const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(99),
          border: Border.all(
            color: selected ? activeColor : const Color(0xFFE2E8F0),
          ),
        ),
        child: Text(
          label,
          style: GoogleFonts.kanit(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: selected ? activeColor : const Color(0xFF64748B),
          ),
        ),
      ),
    );
  }
}
