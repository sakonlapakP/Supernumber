import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';

import 'package:supernumber_admin/main.dart';
import 'package:supernumber_admin/providers/article_provider.dart';
import 'package:supernumber_admin/providers/auth_provider.dart';

void main() {
  testWidgets('shows login screen for unauthenticated users', (WidgetTester tester) async {
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider(create: (_) => AuthProvider()),
          ChangeNotifierProvider(create: (_) => ArticleProvider()),
        ],
        child: const SupernumberAdminApp(),
      ),
    );

    await tester.pumpAndSettle();

    expect(find.text('เข้าสู่ระบบแอดมิน'), findsOneWidget);
    expect(find.text('เข้าสู่ระบบ'), findsOneWidget);
  });
}
