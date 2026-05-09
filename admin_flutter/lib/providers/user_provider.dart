import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import '../services/api_service.dart';

class UserProvider extends ChangeNotifier {
  List<dynamic> _users = [];
  bool _isLoading = false;
  String? _lastErrorMessage;

  List<dynamic> get users => _users;
  bool get isLoading => _isLoading;
  String? get lastErrorMessage => _lastErrorMessage;

  Future<void> fetchUsers() async {
    _isLoading = true;
    _lastErrorMessage = null;
    notifyListeners();

    try {
      final response = await ApiService.dio.get('/users');
      _users = response.data;
    } on DioException catch (e) {
      _lastErrorMessage = _extractErrorMessage(e);
    } catch (e) {
      _lastErrorMessage = 'ไม่สามารถดึงข้อมูลผู้ใช้งานได้';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> createUser(Map<String, dynamic> userData) async {
    _isLoading = true;
    _lastErrorMessage = null;
    notifyListeners();

    try {
      final response = await ApiService.dio.post('/users', data: userData);
      if (response.statusCode == 201) {
        await fetchUsers();
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _lastErrorMessage = _extractErrorMessage(e);
    } catch (e) {
      _lastErrorMessage = 'ไม่สามารถสร้างบัญชีผู้ใช้งานได้';
    }

    _isLoading = false;
    notifyListeners();
    return false;
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
            return value.first.toString();
          }
        }
      }
    }
    return 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
  }
}
