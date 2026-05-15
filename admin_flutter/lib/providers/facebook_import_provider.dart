import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../models/facebook_import_post.model.dart';
import '../services/api_service.dart';

class FacebookImportProvider with ChangeNotifier {
  final List<FacebookImportPost> _posts = [];
  final Set<int> _selectedIds = <int>{};
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
  Set<int> get selectedIds => Set.unmodifiable(_selectedIds);
  bool get hasSelection => _selectedIds.isNotEmpty;
  int get selectedCount => _selectedIds.length;

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
    _selectedIds.clear();
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
          'per_page': 50,
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

  bool isSelected(int id) => _selectedIds.contains(id);

  void toggleSelection(int id) {
    if (_selectedIds.contains(id)) {
      _selectedIds.remove(id);
    } else {
      _selectedIds.add(id);
    }
    notifyListeners();
  }

  void clearSelection() {
    if (_selectedIds.isEmpty) return;
    _selectedIds.clear();
    notifyListeners();
  }

  void selectAllLoaded() {
    if (_posts.isEmpty) return;
    _selectedIds
      ..clear()
      ..addAll(_posts.map((post) => post.id));
    notifyListeners();
  }

  Future<bool> deletePost(int id) async {
    _lastErrorMessage = null;
    notifyListeners();

    try {
      final response = await ApiService.dio.delete('/facebook-imports/$id');
      if (response.statusCode == 200) {
        await fetchFirstPage(
          search: _search,
          fromDate: _fromDate,
          toDate: _toDate,
        );
        return true;
      }

      _lastErrorMessage = 'ลบโพสต์ไม่สำเร็จ (${response.statusCode})';
    } on DioException catch (e) {
      _lastErrorMessage = _extractErrorMessage(e);
    } catch (_) {
      _lastErrorMessage = 'ลบโพสต์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
    }

    notifyListeners();
    return false;
  }

  Future<bool> deleteSelected() async {
    if (_selectedIds.isEmpty) {
      return false;
    }

    _lastErrorMessage = null;
    notifyListeners();

    final ids = _selectedIds.toList();

    try {
      final response = await ApiService.dio.post(
        '/facebook-imports/bulk-delete',
        data: {'ids': ids},
      );

      if (response.statusCode == 200) {
        await fetchFirstPage(
          search: _search,
          fromDate: _fromDate,
          toDate: _toDate,
        );
        return true;
      }

      _lastErrorMessage = 'ลบโพสต์ที่เลือกไม่สำเร็จ (${response.statusCode})';
    } on DioException catch (e) {
      _lastErrorMessage = _extractErrorMessage(e);
    } catch (_) {
      _lastErrorMessage = 'ลบโพสต์ที่เลือกไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
    }

    notifyListeners();
    return false;
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
