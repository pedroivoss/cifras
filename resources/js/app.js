import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Sortable = Sortable;
window.Swal = Swal;

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

/** fetch JSON com CSRF; lança erro com a mensagem do servidor. */
window.api = async (url, method = 'POST', body = null) => {
    const response = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body ? JSON.stringify(body) : null,
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(data.message || 'Não foi possível salvar. Tente novamente.');
    }
    return data;
};

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
});

window.toast = (title, icon = 'success') => Toast.fire({ icon, title });

window.confirmAction = (title, text = '', confirmButtonText = 'Confirmar', danger = true) =>
    Swal.fire({
        title,
        text,
        icon: danger ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: danger ? '#dc2626' : '#4f46e5',
        reverseButtons: true,
        focusCancel: danger,
    }).then((result) => result.isConfirmed);

// Formulários com data-confirm pedem confirmação antes de enviar.
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed) {
        return;
    }
    event.preventDefault();
    const ok = await confirmAction(form.dataset.confirm, form.dataset.confirmText || '', form.dataset.confirmButton || 'Confirmar', form.dataset.confirmDanger !== 'false');
    if (ok) {
        form.dataset.confirmed = '1';
        form.requestSubmit(event.submitter || undefined);
    }
});

window.copyText = async (text, message = 'Copiado!') => {
    try {
        await navigator.clipboard.writeText(text);
        toast(message);
    } catch {
        toast('Não foi possível copiar', 'error');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const flash = document.getElementById('flash');
    if (flash?.dataset.success) {
        toast(flash.dataset.success);
    }
    if (flash?.dataset.error) {
        toast(flash.dataset.error, 'error');
    }
});

/**
 * Editor da playlist: reordenação (SortableJS) e tom por item, salvos automaticamente.
 */
Alpine.data('playlistEditor', ({ items, reorderUrl, itemUrl }) => ({
    items,
    saving: false,

    init() {
        Sortable.create(this.$refs.list, {
            handle: '[data-handle]',
            animation: 150,
            ghostClass: 'opacity-40',
            onEnd: (event) => {
                if (event.oldIndex === event.newIndex) {
                    return;
                }
                const moved = this.items.splice(event.oldIndex, 1)[0];
                this.items.splice(event.newIndex, 0, moved);
                this.saveOrder();
            },
        });
    },

    async saveOrder() {
        this.saving = true;
        try {
            await api(reorderUrl, 'POST', { ids: this.items.map((i) => i.id) });
            toast('Ordem salva');
        } catch (e) {
            toast(e.message, 'error');
        } finally {
            this.saving = false;
        }
    },

    async move(index, delta) {
        const target = index + delta;
        if (target < 0 || target >= this.items.length) {
            return;
        }
        const [moved] = this.items.splice(index, 1);
        this.items.splice(target, 0, moved);
        await this.saveOrder();
    },

    async setSemitones(item, semitones) {
        semitones = ((semitones % 12) + 12) % 12;
        if (semitones > 6) {
            semitones -= 12;
        }
        try {
            const data = await api(itemUrl.replace('__ID__', item.id), 'PATCH', { semitones });
            item.semitones = data.semitones;
            item.key = data.key;
            item.url = item.url.replace(/t=-?\d+/, 't=' + data.semitones);
            toast(data.key ? 'Tom salvo: ' + data.key : 'Tom salvo');
        } catch (e) {
            toast(e.message, 'error');
        }
    },

    async remove(item) {
        if (!(await confirmAction('Remover “' + item.title + '” da playlist?', 'A música continua na biblioteca.', 'Remover'))) {
            return;
        }
        try {
            await api(itemUrl.replace('__ID__', item.id), 'DELETE');
            this.items = this.items.filter((i) => i.id !== item.id);
            toast('Removida da playlist');
        } catch (e) {
            toast(e.message, 'error');
        }
    },

    label(semitones) {
        return semitones > 0 ? '+' + semitones : String(semitones);
    },
}));

Alpine.start();
