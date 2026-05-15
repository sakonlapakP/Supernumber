import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../models/facebook_import_post.model.dart';
import '../services/api_service.dart';

class FacebookImportProvider with ChangeNotifier {
  final List<FacebookImportPost> _posts = [];
  bool _isLoading = false;
  bool _isLoadingMore = false;
  bool _hasMore = true;
  String? _lastErrorMessage;
  int _currentPage = 0;
  int _lastPage = 1;
  String _search = '';
  String _fromDate = '';
  String _toDate = '';

  List<FacebookImportPost> get posts => List.unmodifiable(_posts);
  bool get isLoading => _isLoading;
  bool get isLoadingMore => _isLoadingMore;
  bool get hasMore => _hasMore;
  String? get lastErrorMessage => _lastErrorMessage;

  Future<void> fetchFirstPage({
    String search = '',
    String fromDate = '',
    String toDate = '',
  }) async {
    _search = search.trim();
    _fromDate = fromDate.trim();
    _toDate = toDate.trim();

    _isLoading = true;
    _lastErrorMessage = null;
    _currentPage = 0;
    _lastPage = 1;
    _hasMore = true;
    _posts.clear();
    notifyListeners();

    await _fetchPage(1, append: false);

    _isLoading = false;
    notifyListeners();
  }

  Future<void> fetchNextPage() async {
    if (_isLoading || _isLoadingMore || !_hasMore) return;

    _isLoadingMore = true;
    notifyListeners();

    await _fetchPage(_currentPage + 1, append: true);

    _isLoadingMore = false;
    notifyListeners();
  }

  Future<void> _fetchPage(int page, {required bool append}) async {
    try {
      final response = await ApiService.dio.get(
        '/facebook-imports',
        queryParameters: {
          'page': page,
          'per_page': 30,
          if (_search.isNotEmpty) 'q': _search,
          if (_fromDate.isNotEmpty) 'from': _fromDate,
          if (_toDate.isNotEmpty) 'to': _toDate,
        },
      );

      final payload = response.data;
      if (payload is! Map<String, dynamic>) {
        _lastErrorMessage = 'รูปแบบข้อมูลไม่ถูกต้อง';
        return;
      }

      final List<dynamic> rawItems = payload['data'] is List<dynamic>
          ? payload['data'] as List<dynamic>
          : <dynamic>[];

      final fetched = rawItems
          .whereType<Map<String, dynamic>>()
          .map(FacebookImportPost.fromJson)
          .toList();

      if (!append) {
        _posts
          ..clear()
          ..addAll(fetched);
      } else {
        _posts.addAll(fetched);
      }

      _currentPage = _asInt(payload['current_page']) ?? page;
      _lastPage = _asInt(payload['last_page']) ?? _currentPage;
      _hasMore = _currentPage < _lastPage;
      _lastErrorMessage = null;
    } on DioException catch (e) {
      _lastErrorMessage = _extractErrorMessage(e);
    } catch (_) {
      _lastErrorMessage = 'ไม่สามารถดึงข้อมูลโพสต์ Facebook ได้';
    }
  }

  int? _asInt(dynamic value) {
    if (value is int) return value;
    if (value is String) return int.tryParse(value);
    return null;
  }

  String _extractErrorMessage(DioException error) {
    final data = error.response?.data;
    if (data is Map<String, dynamic>) {
      final message = data['message'];
      if (message is String && message.trim().isNotEmpty) {
        return message;
      }

      final errors = data['errors'];
      if (errors is Map<String, dynamic>) {
        for (final value in errors.values) {
          if (value is List && value.isNotEmpty) {
            final first = value.first;
            if (first is String && first.trim().isNotEmpty) {
              return first;
            }
          }
        }
      }
    }

    return 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
  }
}
