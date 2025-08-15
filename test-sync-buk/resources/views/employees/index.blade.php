<x-layouts.app :title="'Empleados'">
<div x-data="employeesPage()" x-init="init()" class="space-y-6">

  <!-- Banner -->
  <div x-show="flash"
       x-transition
       class="rounded border px-3 py-2"
       :class="flash?.type === 'error' ? 'bg-red-50 border-red-300 text-red-700'
             : flash?.type === 'success' ? 'bg-emerald-50 border-emerald-300 text-emerald-700'
             : 'bg-blue-50 border-blue-300 text-blue-700'"
       x-text="flash?.text"
       style="display:none"></div>

  <div class="flex items-center justify-between">
    <h1 class="text-xl font-bold">Empleados</h1>
    <button @click="startCreate()" class="px-3 py-2 rounded bg-blue-600 text-white">Nuevo</button>
  </div>

  <!-- Tabla -->
  <div class="bg-white rounded shadow">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100 text-left">
          <tr>
            <th class="p-3">ID</th>
            <th class="p-3">Nombre</th>
            <th class="p-3">Correo electrónico</th>
            <th class="p-3">Departamento</th>
            <th class="p-3">Activo</th>
            <th class="p-3 w-40">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="e in items" :key="e.id">
            <tr class="border-t">
              <td class="p-3" x-text="e.id"></td>
              <td class="p-3" x-text="e.name"></td>
              <td class="p-3" x-text="e.email"></td>
              <td class="p-3" x-text="e.department?.name ?? e.department_id ?? '—'"></td>
              <td class="p-3">
                <span x-text="e.is_active ? 'Sí' : 'No'"
                      :class="e.is_active ? 'text-emerald-600' : 'text-gray-500'"></span>
              </td>
              <td class="p-3 space-x-2">
                <button @click="startEdit(e)" class="px-2 py-1 rounded bg-amber-500 text-white">Editar</button>
                <button @click="doDelete(e)" class="px-2 py-1 rounded bg-red-600 text-white">Eliminar</button>
              </td>
            </tr>
          </template>
          <tr x-show="items.length === 0">
            <td colspan="6" class="p-4 text-center text-gray-500">Sin datos</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
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

  <!-- Modal Crear/Editar -->
  <div x-show="modalOpen" class="fixed inset-0 bg-black/30 flex items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded shadow-lg w-full max-w-3xl p-4 space-y-3">
      <h2 class="text-lg font-semibold" x-text="form.id ? 'Editar Employee' : 'Nuevo Employee'"></h2>

      <template x-if="errors.length">
        <div class="text-sm text-red-600" x-text="errors.join(' · ')"></div>
      </template>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <label class="block">
          <span class="text-xs text-gray-600">Nombre</span>
          <input x-model="form.name" class="mt-1 w-full border rounded px-2 py-1">
        </label>
        <label class="block">
          <span class="text-xs text-gray-600">Correo electrónico</span>
          <input type="email" x-model="form.email" class="mt-1 w-full border rounded px-2 py-1">
        </label>
        <label class="block">
          <span class="text-xs text-gray-600">Posición</span>
          <input x-model="form.position" class="mt-1 w-full border rounded px-2 py-1">
        </label>
        <label class="block">
          <span class="text-xs text-gray-600">Fecha de contratación</span>
          <input type="date" x-model="form.hire_date" class="mt-1 w-full border rounded px-2 py-1">
        </label>
        <label class="block">
          <span class="text-xs text-gray-600">Departamento</span>
          <select x-model="form.department_id" class="mt-1 w-full border rounded px-2 py-1">
            <option value="" disabled>— Selecciona —</option>
            <template x-for="d in lookups.departments" :key="d.id">
              <option :value="d.id" x-text="d.name"></option>
            </template>
          </select>
        </label>
        <label class="flex items-center gap-2">
          <input type="checkbox" x-model="form.is_active">
          <span class="text-xs text-gray-600">Activo</span>
        </label>
      </div>

      <!-- Skills del empleado -->
      <div class="border-t pt-3 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold">Habilidades</h3>
          <div class="flex gap-2 items-center">
            <select x-model="skillPicker.id" class="border rounded px-2 py-1">
              <option value="" disabled>— Habilidad —</option>
              <template x-for="s in unselectedSkills()" :key="s.id">
                <option :value="s.id" x-text="s.name"></option>
              </template>
            </select>
            <input type="number" min="0" x-model.number="skillPicker.level" class="w-24 border rounded px-2 py-1" placeholder="Nivel">
            <button @click="addSkill()" class="px-2 py-1 rounded bg-blue-600 text-white">Agregar</button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-left">
              <tr>
                <th class="p-2">Habilidad</th>
                <th class="p-2">Nivel</th>
                <th class="p-2 w-20">Quitar</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="(row,idx) in form.skills" :key="row.id">
                <tr class="border-t">
                  <td class="p-2" x-text="skillName(row.id)"></td>
                  <td class="p-2">
                    <input type="number" min="0" x-model.number="row.level" class="w-24 border rounded px-2 py-1">
                  </td>
                  <td class="p-2">
                    <button @click="removeSkill(idx)" class="px-2 py-1 rounded bg-red-600 text-white">X</button>
                  </td>
                </tr>
              </template>
              <tr x-show="form.skills.length === 0">
                <td colspan="3" class="p-3 text-center text-gray-500">Sin habilidades</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex justify-end gap-2">
        <button @click="closeModal()" class="px-3 py-2 rounded border">Cancelar</button>
        <button @click="save()" class="px-3 py-2 rounded bg-blue-600 text-white">Guardar</button>
      </div>
    </div>
  </div>

</div>

<script>
function employeesPage() {
  return {
    items: [], meta: {},
    lookups: { departments: [], skills: [] },
    modalOpen: false,
    errors: [],
    flash: null,

    form: {
      id: null, name: '', email: '', position: '',
      hire_date: '', department_id: '', is_active: true,
      skills: []
    },
    skillPicker: { id: '', level: 1 },

    apiBase: '/api/system-rrhh/employees',
    depsApi: '/api/system-rrhh/departments?per_page=30',
    skillsApi: '/api/system-rrhh/skills?per_page=30',

    setFlash(text, type='info', ms=4000) {
      this.flash = { text, type };
      if (ms) setTimeout(() => this.flash = null, ms);
    },

    async init() {
      await Promise.all([this.loadLookups(), this.load()]);
    },

    // cargar tablas
    async load(url = null) {
      const res  = await fetch(url ?? this.apiBase);
      const json = await res.json();

      this.items = json.data ?? json;
      this.meta  = json.meta ?? {};

      const l = json.links ?? {};
      this.meta.prev_page_url = l.prev ?? null;
      this.meta.next_page_url = l.next ?? null;
    },
    async loadLookups() {
      const [dRes, sRes] = await Promise.all([fetch(this.depsApi), fetch(this.skillsApi)]);
      const [dJson, sJson] = await Promise.all([dRes.json(), sRes.json()]);
      this.lookups.departments = dJson.data ?? dJson;
      this.lookups.skills      = sJson.data ?? sJson;
    },
    go(url) { if (url) this.load(url); },

    skillName(id) {
      const s = this.lookups.skills.find(x => x.id === id);
      return s ? s.name : id;
    },
    unselectedSkills() {
      const selected = new Set(this.form.skills.map(s => s.id));
      return this.lookups.skills.filter(s => !selected.has(s.id));
    },
    addSkill() {
      if (!this.skillPicker.id) return;
      const exists = this.form.skills.some(s => s.id === this.skillPicker.id);
      if (!exists) {
        this.form.skills.push({ id: this.skillPicker.id, level: Number(this.skillPicker.level) || 0 });
      }
      this.skillPicker = { id: '', level: 1 };
    },
    removeSkill(idx) { this.form.skills.splice(idx, 1); },

    // CRUD
    startCreate() {
      this.errors = [];
      this.form = {
        id: null, name: '', email: '', position: '',
        hire_date: '', department_id: this.lookups.departments[0]?.id ?? '',
        is_active: true, skills: []
      };
      this.modalOpen = true;
    },

    async startEdit(e) {
      this.errors = [];
      // traemos con skills
      const res = await fetch(`${this.apiBase}/${e.id}?include_skills=1`);
      const json = await res.json();
      const data = json.data ?? json;
      const mappedSkills = (data.skills ?? []).map(s => ({
        id: s.id,
        level: Number(s.assignment?.level ?? s.pivot?.level ?? s.level ?? 0)
      }));

      this.form = {
        id: data.id,
        name: data.name ?? '',
        email: data.email ?? '',
        position: data.position ?? '',
        hire_date: (data.hire_date ?? '').slice(0,10),
        department_id: data.department_id ?? '',
        is_active: Boolean(data.is_active ?? true),
        skills: mappedSkills
      };

      this.modalOpen = true;
    },

    closeModal() { this.modalOpen = false; },

    async save() {
      this.errors = [];
      const payload = {
        name: this.form.name,
        email: this.form.email,
        position: this.form.position || null,
        hire_date: this.form.hire_date || null,
        department_id: this.form.department_id,
        is_active: !!this.form.is_active,
        skills: this.form.skills.map(s => ({ id: s.id, level: Number(s.level) || 0 })),
        skills_strategy: 'replace'
      };

      try {
        let res;
        if (this.form.id) {
          res = await fetch(`${this.apiBase}/${this.form.id}`, {
            method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
          });
        } else {
          res = await fetch(this.apiBase, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
          });
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
      } catch {
        this.setFlash('Error de red o servidor','error');
      }
    },

    async doDelete(e) {
      if (!confirm('¿Eliminar este employee?')) return;
      const res = await fetch(`${this.apiBase}/${e.id}`, { method:'DELETE' });
      if (res.status === 204) {
        this.setFlash('Employee eliminado','success');
        await this.load();
      } else {
        const err = await res.json().catch(() => ({}));
        this.setFlash(err.message || 'Error eliminando el employee','error');
      }
    },
  }
}
</script>
</x-layouts.app>
