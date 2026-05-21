class ArticlePlan {
  final int id;
  final DateTime publishDate;
  final String publishTime;
  final String? type;
  final String topic;
  final bool isLottery;
  final String? status; // todo | in_progress | done | blocked | cancelled

  ArticlePlan({
    required this.id,
    required this.publishDate,
    required this.publishTime,
    this.type,
    required this.topic,
    required this.isLottery,
    this.status,
  });

  factory ArticlePlan.fromJson(Map<String, dynamic> json) {
    return ArticlePlan(
      id: json['id'] as int,
      publishDate: DateTime.parse(json['publish_date'].toString()),
      publishTime: (json['publish_time'] ?? '').toString(),
      type: json['type']?.toString(),
      topic: (json['topic'] ?? '').toString(),
      isLottery: json['is_lottery'] == true || json['is_lottery'] == 1,
      status: json['status']?.toString(),
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
      'status': status ?? 'todo',
    };
  }
}

