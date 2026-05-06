import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'dart:convert';
import '../models/article.model.dart';
import '../services/api_service.dart';

class ArticleProvider with ChangeNotifier {
  List<Article> _articles = [];
  bool _isLoading = false;

  List<Article> get articles => _articles;
  bool get isLoading => _isLoading;

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

  Future<bool> saveArticle(Article article, {String? landscapePath, String? squarePath}) async {
    _isLoading = true;
    notifyListeners();

    try {
      final Map<String, dynamic> data = article.toJson();
      
      // แปลง Map เป็น FormData เพื่อส่งไฟล์
      final formData = FormData.fromMap(data);
      
      // ลบฟิลด์ที่เป็น null ออก
      formData.fields.removeWhere((field) => field.value == 'null');

      // แนบไฟล์รูปภาพถ้ามีการเลือกใหม่
      if (landscapePath != null) {
        formData.files.add(MapEntry(
          'cover_landscape',
          await MultipartFile.fromFile(landscapePath, filename: 'landscape.jpg'),
        ));
      }
      if (squarePath != null) {
        formData.files.add(MapEntry(
          'cover_square',
          await MultipartFile.fromFile(squarePath, filename: 'square.jpg'),
        ));
      }
      
      // แปลง imageGuidelines เป็น JSON string
      if (article.imageGuidelines != null) {
        formData.fields.removeWhere((f) => f.key == 'image_guidelines');
        formData.fields.add(MapEntry('image_guidelines', jsonEncode(article.imageGuidelines)));
      }

      Response response;
      if (article.id == null) {
        response = await ApiService.dio.post('/articles', data: formData);
      } else {
        // สำหรับ Update ใน Laravel เมื่อใช้ FormData ต้องใช้ POST และใส่ _method: PUT
        formData.fields.add(const MapEntry('_method', 'PUT'));
        response = await ApiService.dio.post('/articles/${article.id}', data: formData);
      }

      if (response.statusCode == 200 || response.statusCode == 201) {
        await fetchArticles();
        return true;
      }
    } catch (e) {
      debugPrint('Save Article Error: $e');
      if (e is DioException) {
        debugPrint('Response: ${e.response?.data}');
      }
    } finally {
      _isLoading = false;
      notifyListeners();
    }
    return false;
  }
}
