/**
 * Pairing editor — dynamic row add/remove and color swap.
 * Called from beheer/rondes/edit-pairing.blade.php via @push('scripts').
 * Exposes initPairingEditor on window so it can be called from a plain script tag.
 */
export function initPairingEditor(playerOptions) {
    function buildSelectOptions(selectedId = null) {
        let html = '<option value="">— geen speler —</option>';
        playerOptions.forEach(function (p) {
            const sel = (selectedId && String(p.id) === String(selectedId)) ? ' selected' : '';
            html += `<option value="${p.id}"${sel}>${p.name}</option>`;
        });
        return html;
    }

    function getNextIndex() {
        const rows = document.querySelectorAll('#pairings-tbody .pairing-row');
        if (rows.length === 0) return 0;
        let max = 0;
        rows.forEach(function (row) {
            const idx = parseInt(row.dataset.index, 10);
            if (idx > max) max = idx;
        });
        return max + 1;
    }

    function getNextBoardNumber() {
        let max = 0;
        document.querySelectorAll('#pairings-tbody .pairing-row').forEach(function (row) {
            const input = row.querySelector('input[type="number"]');
            if (input) {
                const v = parseInt(input.value, 10);
                if (v > max) max = v;
            }
        });
        return max + 1;
    }

    const addBtn = document.getElementById('add-row-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const noMsg = document.getElementById('no-pairings-msg');
            if (noMsg) noMsg.remove();

            const idx   = getNextIndex();
            const board = getNextBoardNumber();
            const tbody = document.getElementById('pairings-tbody');

            const tr = document.createElement('tr');
            tr.classList.add('pairing-row', 'hover:bg-gray-50', 'dark:hover:bg-gray-800/60');
            tr.dataset.index = idx;

            const inputCls  = 'w-14 border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400';
            const selectCls = 'w-full border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400 min-w-[160px]';

            tr.innerHTML = `
                <td class="px-4 py-3">
                    <input type="number" name="pairings[${idx}][board_number]" value="${board}" min="1" class="${inputCls}">
                </td>
                <td class="px-4 py-3">
                    <select name="pairings[${idx}][white_user_id]" class="${selectCls}">${buildSelectOptions()}</select>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center justify-center p-1.5 text-gray-300 dark:text-gray-600" title="Sla eerst op om kleuren te wisselen">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </span>
                </td>
                <td class="px-4 py-3">
                    <select name="pairings[${idx}][black_user_id]" class="${selectCls}">${buildSelectOptions()}</select>
                </td>
                <td class="px-4 py-3 text-center">
                    <button type="button" onclick="window.pairingRemoveRow(this)" title="Rij verwijderen"
                            class="inline-flex items-center justify-center p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                            aria-label="Rij verwijderen">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </td>`;

            tbody.appendChild(tr);
        });
    }

    // Expose globally for inline onclick handlers
    window.pairingRemoveRow = function (btn) {
        const row = btn.closest('tr');
        if (row) row.remove();
    };

    window.swapColors = function (url, boardNumber) {
        if (!confirm('Kleuren wisselen voor bord ' + boardNumber + '?')) return;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'text/html',
            },
        }).then(function () {
            window.location.reload();
        });
    };
}
