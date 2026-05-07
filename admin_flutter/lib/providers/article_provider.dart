import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'dart:convert';
import '../models/article.model.dart';
import '../services/api_service.dart';

class ArticleProvider with ChangeNotifier {
  List<Article> _articles = [];
  bool _isLoading = false;
  String? _lastErrorMessage;

  List<Article> get articles => _articles;
  bool get isLoading => _isLoading;
  String? get lastErrorMessage => _lastErrorMessage;

  Future<void> fetchArticles() async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await ApiService.dio.get('/articles');
      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'] ?? [];
        _articles = data.map((json) => Article.fromJson(json)).toList();
      }
    } catch (e) {
      debugPrint('Fetch Articles Error: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<Article?> fetchArticleDetail(int id) async {
    try {
      final response = await ApiService.dio.get('/articles/$id');
      if (response.statusCode == 200) {
        final data = response.data is Map && response.data.containsKey('data')
            ? response.data['data']
            : response.data;
        return Article.fromJson(data);
      }
    } catch (e) {
      debugPrint('Fetch Article Detail Error: $e');
    }
    return null;
  }

  Future<String?> fetchPreviewUrl(Article article) async {
    if (article.isPublished && article.publicUrl != null) {
      return article.publicUrl;
    }

    if (article.id == null) return article.publicUrl;

    try {
      final response = await ApiService.dio.get(
        '/articles/${article.id}/preview-url',
      );
      if (response.statusCode == 200) {
        final url = response.data['url'];
        return url is String && url.trim().isNotEmpty ? url : null;
      }
    } catch (e) {
      debugPrint('Fetch Article Preview URL Error: $e');
      if (e is DioException) {
        if (e.response?.statusCode == 404 && article.adminPreviewUrl != null) {
          return article.adminPreviewUrl;
        }
        _lastErrorMessage = _extractErrorMessage(e);
        debugPrint('Response: ${e.response?.data}');
      } else {
        _lastErrorMessage = 'เปิดพรีวิวบทความไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
      }
    }

    return null;
  }

  Future<bool> shareArticle(Article article, String platform) async {
    if (article.id == null) return false;

    _lastErrorMessage = null;

    try {
      final response = await ApiService.dio.post(
        '/articles/${article.id}/share',
        data: {'platform': platform},
      );

      return response.statusCode == 200;
    } catch (e) {
      debugPrint('Share Article Error: $e');
      if (e is DioException) {
        _lastErrorMessage = _extractErrorMessage(e);
        debugPrint('Response: ${e.response?.data}');
      } else {
        _lastErrorMessage = 'แชร์บทความไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
      }
    }

    return false;
  }

  Future<bool> saveArticle(
    Article article, {
    String? landscapePath,
    String? squarePath,
  }) async {
    _isLoading = true;
    _lastErrorMessage = null;
    notifyListeners();

    try {
      final Map<String, dynamic> data = article.toJson();
      data['is_published'] = article.isPublished ? '1' : '0';
      data['is_auto_post'] = article.isAutoPost ? '1' : '0';

      // แปลง Map เป็น FormData เพื่อส่งไฟล์
      final formData = FormData.fromMap(data);

      // ลบฟิลด์ที่เป็น null ออก
      formData.fields.removeWhere((field) => field.value == 'null');

      // แนบไฟล์รูปภาพถ้ามีการเลือกใหม่
      if (landscapePath != null) {
        formData.files.add(
          MapEntry(
            'cover_landscape',
            await MultipartFile.fromFile(
              landscapePath,
              filename: 'landscape.jpg',
            ),
          ),
        );
      }
      if (squarePath != null) {
        formData.files.add(
          MapEntry(
            'cover_square',
            await MultipartFile.fromFile(squarePath, filename: 'square.jpg'),
          ),
        );
      }

      // แปลง imageGuidelines เป็น JSON string
      if (article.imageGuidelines != null) {
        formData.fields.removeWhere((f) => f.key == 'image_guidelines');
        formData.fields.add(
          MapEntry('image_guidelines', jsonEncode(article.imageGuidelines)),
        );
      }

      Response response;
      if (article.id == null) {
        response = await ApiService.dio.post('/articles', data: formData);
      } else {
        // สำหรับ Update ใน Laravel เมื่อใช้ FormData ต้องใช้ POST และใส่ _method: PUT
        formData.fields.add(const MapEntry('_method', 'PUT'));
        response = await ApiService.dio.post(
          '/articles/${article.id}',
          data: formData,
        );
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        await fetchArticles();
        return true;
      }

      _lastErrorMessage = 'บันทึกบทความไม่สำเร็จ (${response.statusCode})';
    } catch (e) {
      debugPrint('Save Article Error: $e');
      if (e is DioException) {
        _lastErrorMessage = _extractErrorMessage(e);
        debugPrint('Response: ${e.response?.data}');
      } else {
        _lastErrorMessage = 'บันทึกบทความไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
      }
    } finally {
      _isLoading = false;
      notifyListeners();
    }
    return false;
  }

  Future<bool> deleteArticle(Article article) async {
    if (article.id == null) return false;

    _isLoading = true;
    _lastErrorMessage = null;
    notifyListeners();

    try {
      final response = await ApiService.dio.delete('/articles/${article.id}');
      if (response.statusCode == 200 || response.statusCode == 204) {
        _articles.removeWhere((item) => item.id == article.id);
        return true;
      }

      _lastErrorMessage = 'ลบบทความไม่สำเร็จ (${response.statusCode})';
    } catch (e) {
      debugPrint('Delete Article Error: $e');
      if (e is DioException) {
        _lastErrorMessage = _extractErrorMessage(e);
        debugPrint('Response: ${e.response?.data}');
      } else {
        _lastErrorMessage = 'ลบบทความไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
      }
    } finally {
      _isLoading = false;
      notifyListeners();
    }

    return false;
  }

  Future<int?> importArticlesFromJson(String jsonData) async {
    _isLoading = true;
    _lastErrorMessage = null;
    notifyListeners();

    try {
      final response = await ApiService.dio.post(
        '/articles/import-json',
        data: {'json_data': jsonData},
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        final imported = response.data['imported'];
        await fetchArticles();
        return imported is int ? imported : int.tryParse(imported.toString());
      }
    } catch (e) {
      debugPrint('Import Articles Error: $e');
      if (e is DioException) {
        _lastErrorMessage = _extractErrorMessage(e);
        debugPrint('Response: ${e.response?.data}');
      } else {
        _lastErrorMessage = 'นำเข้า JSON ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
      }
    } finally {
      _isLoading = false;
      notifyListeners();
    }

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

    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.receiveTimeout ||
        error.type == DioExceptionType.connectionError) {
      return 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ต';
    }

    return 'ดำเนินการไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
  }
}
