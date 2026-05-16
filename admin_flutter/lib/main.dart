import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';
import 'screens/login_screen.dart';
import 'screens/article_list_screen.dart';
import 'providers/auth_provider.dart';
import 'providers/article_provider.dart';
import 'providers/user_provider.dart';
import 'providers/article_plan_provider.dart';
import 'providers/facebook_import_provider.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting();

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => ArticleProvider()),
        ChangeNotifierProvider(create: (_) => UserProvider()),
        ChangeNotifierProvider(create: (_) => ArticlePlanProvider()),
        ChangeNotifierProvider(create: (_) => FacebookImportProvider()),
      ],
      child: const SupernumberAdminApp(),
    ),
  );
}

class SupernumberAdminApp extends StatelessWidget {
  const SupernumberAdminApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Supernumber Admin',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        // เลียนแบบ Admin Web Theme
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF223A63),
          brightness: Brightness.light,
          primary: const Color(0xFF223A63),
          secondary: const Color(0xFF1B8B6F), // admin-success
          surface: Colors.white,
          surfaceContainer: const Color(0xFFEEF4FB), // admin-bg
          onSurface: const Color(0xFF1E2D45), // admin-text
        ),
        scaffoldBackgroundColor: const Color(0xFFEEF4FB),
        textTheme: GoogleFonts.kanitTextTheme(ThemeData.light().textTheme),
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.white,
          foregroundColor: Color(0xFF1E2D45),
          elevation: 0,
          centerTitle: false,
          shape: Border(bottom: BorderSide(color: Color(0xFFD7E1F0), width: 1)),
        ),
        cardTheme: CardThemeData(
          color: Colors.white,
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
            side: const BorderSide(color: Color(0xFFD7E1F0), width: 1),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 12,
          ),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: const BorderSide(color: Color(0xFFC9D7EA)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: const BorderSide(color: Color(0xFFC9D7EA)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: const BorderSide(color: Color(0xFF223A63), width: 2),
          ),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF223A63),
            foregroundColor: Colors.white,
            minimumSize: const Size(double.infinity, 52),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
            textStyle: const TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 16,
            ),
          ),
        ),
      ),
      home: Consumer<AuthProvider>(
        builder: (context, auth, _) {
          return auth.isAuthenticated
              ? const ArticleListScreen()
              : const LoginScreen();
        },
      ),
    );
  }
}
