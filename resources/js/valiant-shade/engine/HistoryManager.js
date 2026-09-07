export class HistoryManager {
    constructor(onApply, captureSnapshot) {
        this.undoStack = [];
        this.redoStack = [];
        this.onApply = onApply;
        this.captureSnapshot = captureSnapshot;
        this.maxSize = 60;
        this.onChange = null;
    }

    baseSnapshot(state) {
        return {
            terrain_data: [...state.terrain_data],
            buildings: state.buildings.map((building) => ({
                id: building.id,
                grid_x: building.grid_x,
                grid_y: building.grid_y,
                label: building.label ?? null,
                building_type_id: building.type?.id ?? building.building_type_id,
            })),
            map: {
                width_tiles: state.map.width_tiles,
                height_tiles: state.map.height_tiles,
            },
        };
    }

    fullSnapshot(state) {
        return {
            ...this.baseSnapshot(state),
            type_sprites: this.captureSnapshot?.() ?? {},
        };
    }

    push(state) {
        this.undoStack.push(this.fullSnapshot(state));
        if (this.undoStack.length > this.maxSize) {
            this.undoStack.shift();
        }
        this.redoStack = [];
        this.notify();
    }

    clear() {
        this.undoStack = [];
        this.redoStack = [];
        this.notify();
    }

    canUndo() {
        return this.undoStack.length > 0;
    }

    canRedo() {
        return this.redoStack.length > 0;
    }

    isDirty() {
        return this.canUndo();
    }

    async undo(currentState) {
        if (!this.canUndo()) {
            return null;
        }
        this.redoStack.push(this.fullSnapshot(currentState));
        const previous = this.undoStack.pop();
        await this.onApply(previous);
        this.notify();
        return previous;
    }

    async redo(currentState) {
        if (!this.canRedo()) {
            return null;
        }
        this.undoStack.push(this.fullSnapshot(currentState));
        const next = this.redoStack.pop();
        await this.onApply(next);
        this.notify();
        return next;
    }

    notify() {
        this.onChange?.({
            canUndo: this.canUndo(),
            canRedo: this.canRedo(),
            isDirty: this.isDirty(),
        });
    }

    setOnChange(callback) {
        this.onChange = callback;
        this.notify();
    }
}
