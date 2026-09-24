<!-- Admin Search Bar Partial -->
<div class="admin-search-box">
    <form action="{{ route('admin.members.index') }}" method="GET" class="d-flex align-items-center mb-0">
        <i data-feather="search" class="search-icon"></i>
        <input 
            type="text" 
            name="q" 
            class="admin-search-input" 
            placeholder="Search workspace..." 
            value="{{ request('q') }}"
            autocomplete="off"
        >
    </form>
</div>
