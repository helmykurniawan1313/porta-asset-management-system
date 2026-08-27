<style>
    .am-options {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 20;
        max-height: 220px;
        overflow-y: auto;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        margin-top: 0.25rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
    .am-options.show {
        display: block;
    }
    .am-option {
        padding: 0.5rem 0.9rem;
        cursor: pointer;
        font-size: 0.9rem;
    }
    .am-option:hover,
    .am-option.active {
        background: var(--porta-accent-soft, rgba(79, 70, 229, 0.1));
    }
    .am-option.no-results {
        color: var(--bs-secondary-color);
        cursor: default;
    }
</style>
