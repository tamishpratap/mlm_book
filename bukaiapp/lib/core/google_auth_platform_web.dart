// ignore: avoid_web_libraries_in_flutter
import 'dart:html' as html;

String getCurrentOrigin() {
  try {
    return html.window.location.origin;
  } catch (_) {
    return '';
  }
}

void redirectToUrl(String url) {
  try {
    html.window.location.href = url;
  } catch (_) {}
}
