@push('styles')
<style>
    .file-upload {
        position: relative;
        min-width: 0;
    }

    .file-upload > .dropzone {
        position: relative;
        width: 100%;
        height: 100%;
        margin: 0;
    }

    .file-upload.wide > .dropzone {
        min-height: 170px;
    }

    .file-upload.insert-zone > .dropzone {
        margin-top: 0;
    }

    .file-upload .file-name {
        max-width: 100%;
        overflow-wrap: anywhere;
    }

    .file-upload > .file-remove {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        padding: 0;
        border: 1px solid #fecaca;
        border-radius: 8px;
        background: #fff;
        color: #dc2626;
        cursor: pointer;
    }

    .file-upload > .file-remove[hidden] {
        display: none;
    }

    .file-upload > .file-remove:hover {
        background: #fef2f2;
        border-color: #f87171;
    }

    .file-upload > .file-remove:focus-visible {
        outline: 2px solid #dc2626;
        outline-offset: 3px;
    }

    .file-upload:focus-within > .dropzone {
        border-color: #2563eb;
    }

    .bulk-file-upload {
        padding-right: 44px;
    }

    .bulk-file-upload > .file-remove {
        top: 8px;
        right: 0;
    }
</style>
@endpush

@push('scripts')
<script>
    (function() {
        const selector = '.file-upload input[type="file"]';

        const updateRemoveButton = input => {
            const button = input.closest('.file-upload').querySelector('[data-clear-file]');
            if (button) button.hidden = input.files.length === 0;
        };

        document.addEventListener('change', event => {
            if (event.target.matches(selector)) updateRemoveButton(event.target);
        });

        document.addEventListener('click', event => {
            const button = event.target.closest('[data-clear-file]');
            if (!button) return;

            event.preventDefault();
            event.stopPropagation();

            const input = button.closest('.file-upload').querySelector('input[type="file"]');
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.focus({ preventScroll: true });
        });

        document.querySelectorAll(selector).forEach(updateRemoveButton);
    })();
</script>
@endpush
