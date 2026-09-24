import 'dart:convert';
import 'package:http/http.dart' as http;
import 'session_manager.dart';

class ApiResponse {
  final bool success;
  final int statusCode;
  final dynamic data;
  final String? message;

  ApiResponse({
    required this.success,
    required this.statusCode,
    this.data,
    this.message,
  });
}

class ApiClient {
  static String get baseUrl => SessionManager.getBaseUrl();

  static Map<String, String> _headers() {
    final token = SessionManager.getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    };
  }

  static Future<ApiResponse> get(String endpoint) async {
    try {
      final uri = Uri.parse('$baseUrl$endpoint');
      final res = await http.get(uri, headers: _headers());
      return _parseResponse(res);
    } catch (e) {
      return ApiResponse(success: false, statusCode: 500, message: 'Network error: $e');
    }
  }

  static Future<ApiResponse> post(String endpoint, [Map<String, dynamic>? body]) async {
    try {
      final uri = Uri.parse('$baseUrl$endpoint');
      final res = await http.post(
        uri,
        headers: _headers(),
        body: body != null ? jsonEncode(body) : null,
      );
      return _parseResponse(res);
    } catch (e) {
      return ApiResponse(success: false, statusCode: 500, message: 'Network error: $e');
    }
  }

  static Future<ApiResponse> delete(String endpoint) async {
    try {
      final uri = Uri.parse('$baseUrl$endpoint');
      final res = await http.delete(uri, headers: _headers());
      return _parseResponse(res);
    } catch (e) {
      return ApiResponse(success: false, statusCode: 500, message: 'Network error: $e');
    }
  }

  static Future<ApiResponse> postMultipart(
    String endpoint, {
    Map<String, String>? fields,
    List<int>? fileBytes,
    String? fileName,
    String fileField = 'media',
  }) async {
    try {
      final token = SessionManager.getToken();
      final uri = Uri.parse('$baseUrl$endpoint');
      final request = http.MultipartRequest('POST', uri);
      request.headers.addAll({
        'Accept': 'application/json',
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      });
      if (fields != null) {
        request.fields.addAll(fields);
      }
      if (fileBytes != null && fileName != null) {
        request.files.add(http.MultipartFile.fromBytes(fileField, fileBytes, filename: fileName));
      }
      final streamedResponse = await request.send();
      final res = await http.Response.fromStream(streamedResponse);
      return _parseResponse(res);
    } catch (e) {
      return ApiResponse(success: false, statusCode: 500, message: 'Network error: $e');
    }
  }

  static ApiResponse _parseResponse(http.Response res) {
    try {
      final json = jsonDecode(res.body);
      final isSuccess = res.statusCode >= 200 && res.statusCode < 300;
      final msg = json is Map ? (json['message'] ?? (isSuccess ? 'Success' : 'Request failed')) : null;

      return ApiResponse(
        success: isSuccess,
        statusCode: res.statusCode,
        data: json,
        message: msg?.toString(),
      );
    } catch (_) {
      return ApiResponse(
        success: res.statusCode >= 200 && res.statusCode < 300,
        statusCode: res.statusCode,
        message: res.body.isNotEmpty ? res.body : 'Response code: ${res.statusCode}',
      );
    }
  }
}
