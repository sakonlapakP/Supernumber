import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../models/article_plan.model.dart';
import '../services/api_service.dart';

class ArticlePlanProvider with ChangeNotifier {
  List<ArticlePlan> _plans = [];
  bool _isLoading = false;
  String? _lastErrorMessage;

  List<ArticlePlan> get plans => _plans;
  bool get isLoading => _isLoading;
  String? get lastErrorMessage => _lastErrorMessage;

  Future<void> fetchPlans({required int year}) async {
    _isLoading = true;
    notifyListeners();
    try {
      final response = await ApiService.dio.get(
        '/article-plans',
        queryParameters: {'plan_year': year},
      );
      final data = (response.data['data'] as List<dynamic>? ?? []);
      _plans = data
          .map((item) => ArticlePlan.fromJson(item as Map<String, dynamic>))
          .toList();
    } catch (e) {
      _lastErrorMessage = 'โหลดแผนบทความไม่สำเร็จ';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> createPlan(Map<String, dynamic> payload, int year) async {
    return _mutate(() => ApiService.dio.post('/article-plans', data: payload), year);
  }

  Future<bool> updatePlan(int id, Map<String, dynamic> payload, int year) async {
    return _mutate(() => ApiService.dio.put('/article-plans/$id', data: payload), year);
  }

  Future<bool> deletePlan(int id, int year) async {
    return _mutate(() => ApiService.dio.delete('/article-plans/$id'), year);
  }

  Future<bool> _mutate(Future<Response> Function() action, int year) async {
    _lastErrorMessage = null;
    try {
      final res = await action();
      if (res.statusCode == 200 || res.statusCode == 201 || res.statusCode == 204) {
        await fetchPlans(year: year);
        return true;
      }
    } catch (e) {
      if (e is DioException) {
        _lastErrorMessage = e.response?.data?['message']?.toString() ?? 'ดำเนินการไม่สำเร็จ';
      } else {
        _lastErrorMessage = 'ดำเนินการไม่สำเร็จ';
      }
    }
    return false;
  }
}

