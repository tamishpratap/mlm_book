class EventModel {
  final int id;
  final String title;
  final String? description;
  final String? location;
  final String? startTime;
  final String? bannerUrl;
  final String organizerName;
  String? myResponse; // 'going', 'interested', or null
  int attendeesCount;

  EventModel({
    required this.id,
    required this.title,
    this.description,
    this.location,
    this.startTime,
    this.bannerUrl,
    required this.organizerName,
    this.myResponse,
    required this.attendeesCount,
  });

  factory EventModel.fromJson(Map<String, dynamic> json) {
    return EventModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      title: json['title']?.toString() ?? '',
      description: json['description']?.toString(),
      location: json['location']?.toString(),
      startTime: json['start_time']?.toString(),
      bannerUrl: json['banner_url']?.toString(),
      organizerName: json['organizer_name']?.toString() ?? 'Organizer',
      myResponse: json['my_response']?.toString(),
      attendeesCount: json['attendees_count'] is int 
          ? json['attendees_count'] 
          : int.tryParse(json['attendees_count']?.toString() ?? '0') ?? 0,
    );
  }
}
