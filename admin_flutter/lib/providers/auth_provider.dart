import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import 'package:dio/dio.dart';

class AuthProvider extends ChangeNotifier {
  bool _isAuthenticated = false;
  String? _token;
  bool _isLoading = false;
  Map<String, dynamic>? _user;

  bool get isAuthenticated => _isAuthenticated;
  bool get isLoading => _isLoading;
  String? get token => _token;
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
    notifyListeners();

    try {
      final response = await ApiService.dio.post('/login', data: {
        'login': login,
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
    } catch (e) {
      debugPrint('Login Error: $e');
    }

    _isLoading = false;
    notifyListeners();
    return false;
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
