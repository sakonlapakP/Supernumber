import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import 'package:dio/dio.dart';

class AuthProvider extends ChangeNotifier {
  bool _isAuthenticated = false;
  String? _token;
  bool _isLoading = false;
  String? _lastErrorMessage;
  Map<String, dynamic>? _user;

  bool get isAuthenticated => _isAuthenticated;
  bool get isLoading => _isLoading;
  String? get token => _token;
  String? get lastErrorMessage => _lastErrorMessage;
  Map<String, dynamic>? get user => _user;

  AuthProvider() {
    _loadAuthStatus();
  }

  Future<void> _loadAuthStatus() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
    _isAuthenticated = _token != null;
    notifyListeners();
  }

  Future<bool> login(String login, String password) async {
    _isLoading = true;
    _lastErrorMessage = null;
    notifyListeners();

    try {
      final response = await ApiService.dio.post('/login', data: {
        'login': login.trim(),
        'password': password,
        'device_name': 'AdminApp', // สามารถเปลี่ยนตาม Device จริงได้
      });

      if (response.statusCode == 200) {
        _token = response.data['token'];
        _user = response.data['user'];
        _isAuthenticated = true;
        
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', _token!);
        
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _lastErrorMessage = _extractErrorMessage(e);
      debugPrint('Login Error: ${e.response?.statusCode} ${e.response?.data ?? e.message}');
    } catch (e) {
      _lastErrorMessage = 'ไม่สามารถเชื่อมต่อระบบได้ กรุณาลองใหม่อีกครั้ง';
      debugPrint('Login Error: $e');
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

    return 'เข้าสู่ระบบไม่สำเร็จ กรุณาตรวจสอบข้อมูลอีกครั้ง';
  }

  Future<void> logout() async {
    try {
      await ApiService.dio.post('/logout');
    } catch (e) {
      debugPrint('Logout Error: $e');
    }
    
    _token = null;
    _isAuthenticated = false;
    _user = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    notifyListeners();
  }
}
