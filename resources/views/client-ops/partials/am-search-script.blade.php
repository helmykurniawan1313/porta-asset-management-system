{{--
    Reusable searchable account-manager dropdown, mirroring the reviewer
    search-select pattern used in performance-review/review-assignments.
    Pass $hiddenId / $searchId / $optionsId matching the element ids used
    for a given instance, since a page can have more than one (e.g. Add
    Client modal + Reassign modal).
--}}
<script>
    (function () {
        const list = @json($accountManagers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values());
        const hiddenInput = document.getElementById('{{ $hiddenId }}');
        const searchInput = document.getElementById('{{ $searchId }}');
        const optionsBox = document.getElementById('{{ $optionsId }}');

        if (!hiddenInput || !searchInput || !optionsBox) {
            return;
        }

        function renderOptions(term) {
            const filtered = list.filter(u => u.name.toLowerCase().includes(term.toLowerCase()));
            optionsBox.innerHTML = '';

            if (filtered.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'am-option no-results';
                empty.textContent = 'No matching account managers.';
                optionsBox.appendChild(empty);
                return;
            }

            filtered.forEach(function (u) {
                const option = document.createElement('div');
                option.className = 'am-option';
                option.textContent = u.name;
                option.addEventListener('click', function () {
                    hiddenInput.value = u.id;
                    searchInput.value = u.name;
                    optionsBox.classList.remove('show');
                });
                optionsBox.appendChild(option);
            });
        }

        searchInput.addEventListener('focus', function () {
            renderOptions(searchInput.value);
            optionsBox.classList.add('show');
        });

        searchInput.addEventListener('input', function () {
            hiddenInput.value = '';
            renderOptions(searchInput.value);
            optionsBox.classList.add('show');
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.am-select')) {
                optionsBox.classList.remove('show');
            }
        });
    })();
</script>
