import 'package:intl/intl.dart';

class DateFormatter {
  /// Converts a DateTime to Bangkok time (+7) and formats it.
  /// Even if the device is in another timezone, this will show the Bangkok time.
  static String formatBangkok(DateTime? dateTime, {String format = 'dd/MM/yyyy, HH:mm'}) {
    if (dateTime == null) return '-';
    
    // Convert to UTC first, then add 7 hours for Bangkok
    final bangkokTime = dateTime.toUtc().add(const Duration(hours: 7));
    return DateFormat(format).format(bangkokTime);
  }

  /// Special format for Article list
  static String formatArticleList(DateTime? dateTime) {
    return formatBangkok(dateTime, format: 'dd MMM yyyy | HH:mm');
  }
}
