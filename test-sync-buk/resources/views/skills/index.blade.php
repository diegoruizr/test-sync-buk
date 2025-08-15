<x-layouts.app :title="'Habilidades'">
<div x-data="skillsPage()" x-init="load()" class="space-y-6">

     <!-- Banner de mensajes -->
    <div x-show="flash"
        x-transition
        class="rounded border px-3 py-2"
        :class="flash?.type === 'error' ? 'bg-red-50 border-red-300 text-red-700'
                : flash?.type === 'success' ? 'bg-emerald-50 border-emerald-300 text-emerald-700'
                : 'bg-blue-50 border-blue-300 text-blue-700'"
        x-text="flash?.text"
        style="display:none"></div>

  <div class="flex items-center justify-between">
    <h1 class="text-xl font-bold">Habilidades</h1>
    <button @click="startCreate()" class="px-3 py-2 rounded bg-blue-600 text-white">Nuevo</button>
  </div>

  <div class="bg-white rounded shadow">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100 text-left">
          <tr>
            <th class="p-3">ID</th>
            <th class="p-3">Nombre</th>
            <th class="p-3">Nivel requerido</th>
            <th class="p-3">¿Eliminado?</th>
            <th class="p-3 w-40">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="s in items" :key="s.id">
            <tr class="border-t">
              <td class="p-3" x-text="s.id"></td>
              <td class="p-3" x-text="s.name"></td>
              <td class="p-3" x-text="s.level_required"></td>
              <td class="p-3">
                <span x-text="s.deleted_at ? 'Sí' : 'No'"
                      :class="s.deleted_at ? 'text-red-600' : 'text-green-600'"></span>
              </td>
              <td class="p-3 space-x-2">
                <button @click="startEdit(s)" class="px-2 py-1 rounded bg-amber-500 text-white">Editar</button>
                <template x-if="!s.deleted_at">
                  <button @click="doDelete(s)" class="px-2 py-1 rounded bg-red-600 text-white">Eliminar</button>
                </template>
                <template x-if="s.deleted_at">
                  <button @click="doRestore(s)" class="px-2 py-1 rounded bg-emerald-600 text-white">Restaurar</button>
                </template>
              </td>
            </tr>
          </template>
          <tr x-show="items.length === 0">
            <td colspan="5" class="p-4 text-center text-gray-500">Sin datos</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between p-3">
      <div class="text-xs text-gray-500">
        <span x-text="`Mostrando ${meta.from ?? 0}–${meta.to ?? 0} de ${meta.total ?? 0}`"></span>
      </div>
      <div class="space-x-2">
        <button @click="go(meta.prev_page_url)" :disabled="!meta.prev_page_url"
          class="px-2 py-1 rounded border disabled:opacity-50">Anterior</button>
        <button @click="go(meta.next_page_url)" :disabled="!meta.next_page_url"
          class="px-2 py-1 rounded border disabled:opacity-50">Siguiente</button>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div x-show="modalOpen" class="fixed inset-0 bg-black/30 flex items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded shadow-lg w-full max-w-lg p-4 space-y-3">
      <h2 class="text-lg font-semibold" x-text="form.id ? 'Editar Skill' : 'Nueva Skill'"></h2>

      <template x-if="errors.length">
        <div class="text-sm text-red-600" x-text="errors.join(' · ')"></div>
      </template>

      <div class="space-y-2">
        <label class="block">
          <span class="text-xs text-gray-600">Nombre</span>
          <input x-model="form.name" class="mt-1 w-full border rounded px-2 py-1">
        </label>
        <label class="block">
          <span class="text-xs text-gray-600">Nivel requerido</span>
          <input type="number" x-model.number="form.level_required" min="0" class="mt-1 w-full border rounded px-2 py-1">
        </label>
      </div>

      <div class="flex justify-end gap-2">
        <button @click="closeModal()" class="px-3 py-2 rounded border">Cancelar</button>
        <button @click="save()" class="px-3 py-2 rounded bg-blue-600 text-white">Guardar</button>
      </div>
    </div>
  </div>

</div>

<script>
function skillsPage() {
  return {
    items: [], meta: {},
    modalOpen: false,
    form: { id: null, name: '', level_required: 1 },
    errors: [],
    flash: null,
    apiBase: '/api/system-rrhh/skills',

    setFlash(text, type='info', ms=4000) {
      this.flash = { text, type };
      if (ms) setTimeout(() => this.flash = null, ms);
    },

    async load(url = null) {
      const res  = await fetch(url ?? this.apiBase);
      const json = await res.json();

      this.items = json.data ?? json;
      this.meta  = json.meta ?? {};

      const l = json.links ?? {};
      this.meta.prev_page_url = l.prev ?? null;
      this.meta.next_page_url = l.next ?? null;
    },
    go(url) { if (url) this.load(url); },

    startCreate() { this.form = { id:null, name:'', level_required:1 }; this.errors=[]; this.modalOpen=true; },
    startEdit(s)  { this.form = { id:s.id, name:s.name, level_required:s.level_required ?? 1 }; this.errors=[]; this.modalOpen=true; },
    closeModal()  { this.modalOpen=false; },

    async save() {
      this.errors = [];
      const payload = { name:this.form.name, level_required:Number(this.form.level_required) };
      let res;
      if (this.form.id) {
        res = await fetch(`${this.apiBase}/${this.form.id}`, { method:'PUT', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
      } else {
        res = await fetch(this.apiBase, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
      }
      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        this.errors = Object.values(err.errors ?? {}).flat();
        if (err.message) this.setFlash(err.message, 'error');
        return;
      }
      this.modalOpen = false;
      this.setFlash('Guardado correctamente','success');
      await this.load();
    },

    async doDelete(s) {
      if (!confirm('¿Eliminar esta skill?')) return;
      const res = await fetch(`${this.apiBase}/${s.id}`, { method:'DELETE' });

      if (res.status === 204) {
        this.setFlash('Skill eliminada','success');
        await this.load();
        return;
      }

      // Manejo de errores
      let err = {};
      try { err = await res.json(); } catch {}
      if (res.status === 409 && err.code === 'SKILL_HAS_EMPLOYEES') {
        this.setFlash(err.message || 'No se puede eliminar: está asociada a empleados','error');
      } else {
        this.setFlash(err.message || 'Error eliminando la skill','error');
      }
    },

    async doRestore(s) {
      const res = await fetch(`${this.apiBase}/${s.id}/restore`, { method:'POST' });
      if (res.ok) {
        this.setFlash('Skill restaurada','success');
        await this.load();
      } else {
        const err = await res.json().catch(() => ({}));
        this.setFlash(err.message || 'Error al restaurar','error');
      }
    }
  }
}
</script>
</x-layouts.app>
