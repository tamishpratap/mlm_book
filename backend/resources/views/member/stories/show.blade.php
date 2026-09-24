@extends('member.layouts.app')

@section('title', $story->member->name."'s Story")
@section('body-class', 'story-viewer-page')

@section('content')
    @include('member.stories.partials.viewer', [
        'story' => $story,
        'standalone' => true,
        'previousStoryId' => $previousStoryId ?? null,
        'nextStoryId' => $nextStoryId ?? null,
    ])
@endsection
