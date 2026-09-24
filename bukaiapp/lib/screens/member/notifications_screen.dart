import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../models/notification_model.dart';
import '../../providers/notification_provider.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<NotificationProvider>().fetchNotifications();
    });
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<NotificationProvider>();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        title: const Text('Notifications', style: TextStyle(color: AppColors.textHeading, fontWeight: FontWeight.bold, fontSize: 18)),
        actions: [
          if (provider.unreadCount > 0)
            TextButton(
              onPressed: () => context.read<NotificationProvider>().markAllAsRead(),
              child: const Text('Mark all read', style: TextStyle(color: AppColors.primary, fontSize: 13, fontWeight: FontWeight.bold)),
            ),
        ],
      ),
      body: provider.isLoading
          ? const Center(child: CircularProgressIndicator())
          : provider.notifications.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.notifications_none_rounded, size: 64, color: AppColors.textMuted.withOpacity(0.5)),
                      const SizedBox(height: 12),
                      const Text('You have no notifications yet.', style: TextStyle(color: AppColors.textSecondary)),
                    ],
                  ),
                )
              : ListView.separated(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  itemCount: provider.notifications.length,
                  separatorBuilder: (context, index) => const Divider(height: 1, color: AppColors.borderSoft),
                  itemBuilder: (ctx, idx) {
                    final n = provider.notifications[idx];
                    return _buildNotificationTile(n);
                  },
                ),
    );
  }

  Widget _buildNotificationTile(NotificationModel n) {
    IconData icon;
    Color iconColor;

    switch (n.type.toLowerCase()) {
      case 'like':
      case 'react':
        icon = Icons.favorite_rounded;
        iconColor = Colors.pinkAccent;
        break;
      case 'comment':
        icon = Icons.chat_bubble_rounded;
        iconColor = AppColors.primary;
        break;
      case 'friend':
        icon = Icons.person_add_rounded;
        iconColor = AppColors.secondary;
        break;
      case 'reward':
      case 'wallet':
        icon = Icons.stars_rounded;
        iconColor = Colors.amber;
        break;
      default:
        icon = Icons.notifications_active_rounded;
        iconColor = AppColors.primary;
    }

    return ListTile(
      tileColor: n.isRead ? Colors.transparent : AppColors.primarySoft.withOpacity(0.4),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      leading: CircleAvatar(
        radius: 22,
        backgroundColor: iconColor.withOpacity(0.12),
        child: Icon(icon, color: iconColor, size: 22),
      ),
      title: Text(
        n.title,
        style: TextStyle(
          fontSize: 14,
          fontWeight: n.isRead ? FontWeight.normal : FontWeight.bold,
          color: AppColors.textHeading,
        ),
      ),
      subtitle: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 3),
          Text(
            n.message,
            style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
          ),
          const SizedBox(height: 4),
          Text(
            n.createdAt,
            style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
          ),
        ],
      ),
      trailing: n.isRead
          ? null
          : Container(
              width: 8,
              height: 8,
              decoration: const BoxDecoration(
                color: AppColors.primary,
                shape: BoxShape.circle,
              ),
            ),
      onTap: () {
        if (!n.isRead) {
          context.read<NotificationProvider>().markAsRead(n);
        }
      },
    );
  }
}
