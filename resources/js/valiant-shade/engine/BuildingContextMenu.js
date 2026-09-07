export class BuildingContextMenu {
    constructor() {
        this.root = document.createElement('div');
        this.root.className = 'vs-context-menu';
        this.root.hidden = true;
        this.building = null;
        this.actions = {};
        document.body.appendChild(this.root);

        document.addEventListener('click', () => this.hide());
        document.addEventListener('contextmenu', (event) => {
            if (!event.target.closest('.vs-context-menu')) {
                this.hide();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.hide();
            }
        });
    }

    setActions(actions) {
        this.actions = actions;
    }

    show(building, clientX, clientY) {
        this.building = building;
        const { type } = building;
        const label = building.label ?? type.name;

        this.root.replaceChildren();

        this.addItem('İsim değiştir…', () => {
            const next = window.prompt('Yapı adı', label);
            if (next !== null && next.trim() !== '') {
                this.actions.onRename?.(building, next.trim());
            }
        });

        this.addItem('Görsel ekle…', () => {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.hidden = true;
            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (file) {
                    this.actions.onUploadSprite?.(type.id, file);
                }
                input.remove();
            });
            document.body.appendChild(input);
            input.click();
        });

        if (type.sprite_url) {
            this.addItem('Görseli kaldır', () => {
                this.actions.onRemoveSprite?.(type.id);
            }, true);
        }

        this.addItem('Haritadan kaldır', () => {
            this.actions.onRemove?.(building);
        }, true);

        this.root.hidden = false;

        const rect = this.root.getBoundingClientRect();
        const x = Math.min(clientX, window.innerWidth - rect.width - 8);
        const y = Math.min(clientY, window.innerHeight - rect.height - 8);
        this.root.style.left = `${Math.max(8, x)}px`;
        this.root.style.top = `${Math.max(8, y)}px`;
    }

    addItem(label, handler, danger = false) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `vs-context-menu-item${danger ? ' vs-context-menu-item--danger' : ''}`;
        button.textContent = label;
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            this.hide();
            handler();
        });
        this.root.appendChild(button);
    }

    hide() {
        this.root.hidden = true;
        this.building = null;
    }
}
