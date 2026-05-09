import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../providers/user_provider.dart';

class AdminRegisterScreen extends StatefulWidget {
  const AdminRegisterScreen({super.key});

  @override
  State<AdminRegisterScreen> createState() => _AdminRegisterScreenState();
}

class _AdminRegisterScreenState extends State<AdminRegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _usernameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  
  String _selectedRole = 'admin';
  bool _isActive = true;

  final List<Map<String, String>> _roles = [
    {'value': 'manager', 'label': 'Manager (สิทธิ์สูงสุด)'},
    {'value': 'admin', 'label': 'Admin (จัดการทั่วไป)'},
    {'value': 'staff', 'label': 'Staff (อ่านอย่างเดียว)'},
  ];

  Future<void> _handleSubmit() async {
    if (!_formKey.currentState!.validate()) return;

    final provider = context.read<UserProvider>();
    final success = await provider.createUser({
      'name': _nameController.text.trim(),
      'username': _usernameController.text.trim(),
      'email': _emailController.text.trim(),
      'password': _passwordController.text,
      'password_confirmation': _confirmPasswordController.text,
      'role': _selectedRole,
      'is_active': _isActive,
    });

    if (!mounted) return;

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('สร้างบัญชีผู้ใช้งานสำเร็จ'),
          backgroundColor: Color(0xFF1B8B6F),
        ),
      );
      Navigator.pop(context);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(provider.lastErrorMessage ?? 'เกิดข้อผิดพลาดในการสร้างบัญชี'),
          backgroundColor: const Color(0xFFC54B3D),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'เพิ่มผู้ดูแลระบบใหม่',
          style: GoogleFonts.kanit(fontWeight: FontWeight.bold),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Center(
          child: Container(
            constraints: const BoxConstraints(maxWidth: 600),
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'ข้อมูลพื้นฐาน',
                        style: GoogleFonts.kanit(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: const Color(0xFF223A63),
                        ),
                      ),
                      const SizedBox(height: 24),
                      TextFormField(
                        controller: _nameController,
                        decoration: const InputDecoration(
                          labelText: 'ชื่อ-นามสกุล',
                          prefixIcon: Icon(Icons.badge_outlined),
                        ),
                        validator: (v) => v!.isEmpty ? 'กรุณากรอกชื่อ-นามสกุล' : null,
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _emailController,
                        keyboardType: TextInputType.emailAddress,
                        decoration: const InputDecoration(
                          labelText: 'อีเมล',
                          prefixIcon: Icon(Icons.email_outlined),
                        ),
                        validator: (v) {
                          if (v!.isEmpty) return 'กรุณากรอกอีเมล';
                          if (!v.contains('@')) return 'รูปแบบอีเมลไม่ถูกต้อง';
                          return null;
                        },
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _usernameController,
                        decoration: const InputDecoration(
                          labelText: 'ชื่อผู้ใช้งาน (Username)',
                          prefixIcon: Icon(Icons.person_outline),
                        ),
                        validator: (v) => v!.isEmpty ? 'กรุณากรอกชื่อผู้ใช้งาน' : null,
                      ),
                      const SizedBox(height: 32),
                      Text(
                        'ความปลอดภัย',
                        style: GoogleFonts.kanit(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: const Color(0xFF223A63),
                        ),
                      ),
                      const SizedBox(height: 24),
                      TextFormField(
                        controller: _passwordController,
                        obscureText: true,
                        decoration: const InputDecoration(
                          labelText: 'รหัสผ่าน',
                          prefixIcon: Icon(Icons.lock_outline),
                        ),
                        validator: (v) => v!.length < 8 ? 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร' : null,
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _confirmPasswordController,
                        obscureText: true,
                        decoration: const InputDecoration(
                          labelText: 'ยืนยันรหัสผ่าน',
                          prefixIcon: Icon(Icons.lock_reset_outlined),
                        ),
                        validator: (v) {
                          if (v != _passwordController.text) return 'รหัสผ่านไม่ตรงกัน';
                          return null;
                        },
                      ),
                      const SizedBox(height: 32),
                      Text(
                        'การกำหนดสิทธิ์',
                        style: GoogleFonts.kanit(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: const Color(0xFF223A63),
                        ),
                      ),
                      const SizedBox(height: 16),
                      DropdownButtonFormField<String>(
                        value: _selectedRole,
                        decoration: const InputDecoration(
                          labelText: 'ตำแหน่ง/บทบาท',
                          prefixIcon: Icon(Icons.admin_panel_settings_outlined),
                        ),
                        items: _roles.map((role) {
                          return DropdownMenuItem(
                            value: role['value'],
                            child: Text(role['label']!, style: GoogleFonts.kanit()),
                          );
                        }).toList(),
                        onChanged: (v) => setState(() => _selectedRole = v!),
                      ),
                      const SizedBox(height: 24),
                      SwitchListTile(
                        title: Text(
                          'สถานะการใช้งาน',
                          style: GoogleFonts.kanit(fontWeight: FontWeight.w500),
                        ),
                        subtitle: Text(
                          _isActive ? 'บัญชีพร้อมใช้งาน' : 'ระงับการใช้งานชั่วคราว',
                          style: GoogleFonts.kanit(fontSize: 12),
                        ),
                        value: _isActive,
                        activeColor: const Color(0xFF1B8B6F),
                        onChanged: (v) => setState(() => _isActive = v),
                      ),
                      const SizedBox(height: 40),
                      Consumer<UserProvider>(
                        builder: (context, provider, _) {
                          return ElevatedButton(
                            onPressed: provider.isLoading ? null : _handleSubmit,
                            child: provider.isLoading
                                ? const SizedBox(
                                    height: 20,
                                    width: 20,
                                    child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                  )
                                : const Text('บันทึกข้อมูล'),
                          );
                        },
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
