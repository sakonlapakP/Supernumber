class ArticlePlan {
  final int id;
  final DateTime publishDate;
  final String publishTime;
  final String? type;
  final String topic;
  final bool isLottery;
  final String status; // todo, in_progress, done, blocked, cancelled
  final bool isArticleReady;
  final String? assignedTo;
  final DateTime? dueDate;
  final String? blockedReason;
  final String? notes;
  final int? articleId;

  ArticlePlan({
    required this.id,
    required this.publishDate,
    required this.publishTime,
    this.type,
    required this.topic,
    required this.isLottery,
    this.status = 'todo',
    this.isArticleReady = false,
    this.assignedTo,
    this.dueDate,
    this.blockedReason,
    this.notes,
    this.articleId,
  });

  static const Map<String, String> statusLabels = {
    'todo': 'วางแผน',
    'in_progress': 'กำลังทำ',
    'done': 'เสร็จแล้ว',
    'blocked': 'ติดขัด',
    'cancelled': 'ยกเลิก',
  };

  String get statusLabel => statusLabels[status] ?? status;

  bool get isDone => status == 'done';
  bool get isBlocked => status == 'blocked';
  bool get isCancelled => status == 'cancelled';
  bool get isInProgress => status == 'in_progress';

  factory ArticlePlan.fromJson(Map<String, dynamic> json) {
    return ArticlePlan(
      id: json['id'] as int,
      publishDate: DateTime.parse(json['publish_date'].toString()),
      publishTime: (json['publish_time'] ?? '').toString(),
      type: json['type']?.toString(),
      topic: (json['topic'] ?? '').toString(),
      isLottery: json['is_lottery'] == true || json['is_lottery'] == 1,
      status: (json['status'] ?? 'todo').toString(),
      isArticleReady: json['is_article_ready'] == true || json['is_article_ready'] == 1,
      assignedTo: json['assigned_to']?.toString(),
      dueDate: json['due_date'] != null
          ? DateTime.tryParse(json['due_date'].toString())
          : null,
      blockedReason: json['blocked_reason']?.toString(),
      notes: json['notes']?.toString(),
      articleId: json['article_id'] as int?,
    );
  }

  Map<String, dynamic> toPayload() {
    return {
      'publish_date':
          '${publishDate.year.toString().padLeft(4, '0')}-${publishDate.month.toString().padLeft(2, '0')}-${publishDate.day.toString().padLeft(2, '0')}',
      'publish_time': publishTime,
      'type': type,
      'topic': topic,
      'is_lottery': isLottery,
      'status': status,
    };
  }

  ArticlePlan copyWith({String? status, String? notes, String? blockedReason}) {
    return ArticlePlan(
      id: id,
      publishDate: publishDate,
      publishTime: publishTime,
      type: type,
      topic: topic,
      isLottery: isLottery,
      status: status ?? this.status,
      isArticleReady: isArticleReady,
      assignedTo: assignedTo,
      dueDate: dueDate,
      blockedReason: blockedReason ?? this.blockedReason,
      notes: notes ?? this.notes,
      articleId: articleId,
    );
  }
}
