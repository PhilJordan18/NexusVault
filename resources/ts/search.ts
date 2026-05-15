const searchInput = document.getElementById('search-input') as HTMLInputElement;
const dropdown = document.getElementById('search-dropdown') as HTMLDivElement;

let debounceTimer: ReturnType<typeof setTimeout>;

if (searchInput && dropdown) {
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = searchInput.value.trim();

        if (query.length === 0) {
            dropdown.classList.add('hidden');

            return;
        }

        debounceTimer = setTimeout(() => fetchResults(query), 300);
    });

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
            searchInput.blur();
        }
    });

    document.addEventListener('click', (e) => {
        if (!(e.target as HTMLElement).closest('#search-input') &&
            !(e.target as HTMLElement).closest('#search-dropdown')) {
            dropdown.classList.add('hidden');
        }
    });
}

async function fetchResults(query: string) {
    dropdown.classList.remove('hidden');
    dropdown.innerHTML = '<div class="px-4 py-4 text-center text-white/40">Searching…</div>';

    try {
        const res = await fetch(`/search?q=${encodeURIComponent(query)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            }
        });
        const html = await res.text();
        dropdown.innerHTML = html;
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (e) {
        dropdown.innerHTML = '<div class="px-4 py-4 text-center text-red-400">Error</div>';
    }
}
