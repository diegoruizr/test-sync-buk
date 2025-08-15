<x-layouts.app :title="'Empleados'">
  <div x-data="employeesPage()" x-init="init()" class="space-y-4">

    <h1 class="text-2xl font-semibold">Empleados</h1>

    <!-- Filtros -->
    <form @submit.prevent="applyFilters" class="bg-white rounded-lg shadow p-4 grid grid-cols-1 md:grid-cols-7 gap-3">
      <input x-model="filters.q" type="text" placeholder="Buscar nombre, email o cargo" class="md:col-span-2 border rounded px-3 py-2">
      <select x-model="filters.department" class="border rounded px-3 py-2">
        <option value="">Todos los departamentos</option>
        <template x-for="d in depts" :key="d.id">
          <option :value="d.id" x-text="d.name"></option>
        </template>
      </select>
      <select x-model="filters.active" class="border rounded px-3 py-2">
        <option value="">Todos</option>
        <option value="1">Activos</option>
        <option value="0">Inactivos</option>
      </select>
      <input x-model="filters.from" type="date" class="border rounded px-3 py-2">
      <input x-model="filters.to" type="date" class="border rounded px-3 py-2">
      <select x-model="filters.sort" class="border rounded px-3 py-2">
        <template x-for="field in sortFields" :key="field.value">
          <option :value="field.value" x-text="field.label"></option>
        </template>
      </select>
      <div class="md:col-span-7 flex items-center gap-3">
        <select x-model.number="filters.per_page" class="border rounded px-3 py-2">
          <template x-for="pp in [15,25,50,100]" :key="pp">
            <option :value="pp" x-text="pp + '/página'"></option>
          </template>
        </select>
        <select x-model="filters.dir" class="border rounded px-3 py-2">
          <option value="desc">Descendente</option>
          <option value="asc">Ascendente</option>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filtrar</button>
      </div>
    </form>

    <!-- Tabla -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 text-left">
            <tr>
              <th class="px-4 py-2 w-20">ID</th>
              <th class="px-4 py-2">Nombre</th>
              <th class="px-4 py-2">Email</th>
              <th class="px-4 py-2">Cargo</th>
              <th class="px-4 py-2">Departamento</th>
              <th class="px-4 py-2">Ingreso</th>
              <th class="px-4 py-2">Estado</th>
              <th class="px-4 py-2">Actualizado</th>
              <th class="px-4 py-2 w-28"></th>
            </tr>
          </thead>
          <tbody>
            <template x-if="loading">
              <tr><td colspan="9" class="px-4 py-6 text-gray-500">Cargando...</td></tr>
            </template>

            <template x-if="!loading && items.length === 0">
              <tr><td colspan="9" class="px-4 py-6 text-gray-500">Sin resultados</td></tr>
            </template>

            <template x-for="e in items" :key="e.id">
              <tr class="border-t">
                <td class="px-4 py-2" x-text="e.id"></td>
                <td class="px-4 py-2" x-text="e.name"></td>
                <td class="px-4 py-2" x-text="e.email"></td>
                <td class="px-4 py-2" x-text="e.position"></td>
                <td class="px-4 py-2" x-text="e.department?.name ?? ''"></td>
                <td class="px-4 py-2" x-text="e.hire_date ?? ''"></td>
                <td class="px-4 py-2">
                  <span :class="e.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700'"
                        class="px-2 py-0.5 rounded text-xs"
                        x-text="e.is_active ? 'Activo' : 'Inactivo'"></span>
                </td>
                <td class="px-4 py-2" x-text="formatDate(e.updated_at)"></td>
                <td class="px-4 py-2 text-right">
                  <button @click="open(e.id)" class="px-3 py-1 border rounded hover:bg-gray-50">Ver</button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div class="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
        <div class="text-gray-600 text-sm" x-text="`Mostrando ${meta.from ?? 0}-${meta.to ?? 0} de ${meta.total ?? 0}`"></div>
        <div class="flex gap-2">
          <button @click="go(meta.current_page - 1)" :disabled="meta.current_page <= 1"
                  class="px-3 py-1 border rounded disabled:opacity-50">Anterior</button>
          <button @click="go(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page"
                  class="px-3 py-1 border rounded disabled:opacity-50">Siguiente</button>
        </div>
      </div>
    </div>

    <!-- Modal (solo lectura) -->
    <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" style="display: none;">
      <div @click.outside="showModal=false" class="bg-white w-full max-w-3xl rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Employee</h2>
          <button @click="showModal=false" class="text-gray-500 hover:text-gray-700">✕</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm text-gray-600 mb-1">ID</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100" :value="detail.id" disabled>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Departamento</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100"
                   :value="detail.department?.name ?? ''" disabled>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Nombre</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100" :value="detail.name" disabled>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Email</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100" :value="detail.email" disabled>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Cargo</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100" :value="detail.position" disabled>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Fecha de ingreso</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100" :value="detail.hire_date" disabled>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Estado</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100"
                   :value="detail.is_active ? 'Activo' : 'Inactivo'" disabled>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Actualizado</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100" :value="detail.updated_at" disabled>
          </div>
        </div>

        <!-- Skills del empleado -->
        <div class="mt-6">
          <h3 class="font-semibold mb-2">Skills</h3>
          <div class="overflow-x-auto border rounded">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-3 py-2 text-left">ID</th>
                  <th class="px-3 py-2 text-left">Nombre</th>
                  <th class="px-3 py-2 text-left">Nivel (empleado)</th>
                </tr>
              </thead>
              <tbody>
                <template x-if="!detail.skills || detail.skills.length === 0">
                  <tr><td colspan="4" class="px-3 py-3 text-gray-500">Sin skills asociados</td></tr>
                </template>
                <template x-for="s in detail.skills" :key="s.id">
                  <tr class="border-t">
                    <td class="px-3 py-2" x-text="s.id"></td>
                    <td class="px-3 py-2" x-text="s.name"></td>
                    <td class="px-3 py-2" x-text="s.level ?? '-'"></td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <div class="mt-6 text-right">
          <button @click="showModal=false" class="px-4 py-2 border rounded hover:bg-gray-50">Cerrar</button>
        </div>
      </div>
    </div>

  </div>

  <script>
  function employeesPage(){
    return {
      apiBase: '/api/system-attendance/employees',
      apiDepts: '/api/system-attendance/departments',
      items: [], meta: {}, loading: false,
      depts: [],
      showModal: false,
      detail: {
        id:'', name:'', email:'', position:'', hire_date:'',
        is_active:false, updated_at:'', department:null, skills:[]
      },
      filters: {
        q:'', department:'', active:'', from:'', to:'',
        sort:'updated_at', dir:'desc', per_page:15
      },
      sortFields: [
        { value: 'updated_at', label: 'Actualizado' },
        { value: 'created_at', label: 'Creado' },
        { value: 'name', label: 'Nombre' },
        { value: 'email', label: 'Email' },
        { value: 'position', label: 'Cargo' },
        { value: 'hire_date', label: 'Fecha de ingreso' },
        { value: 'is_active', label: 'Estado' },
        { value: 'id', label: 'ID' }
      ],
      async init(){
        await this.loadDepts();
        this.load(1);
      },
      async loadDepts(){
        try{
          const res = await fetch(`${this.apiDepts}?per_page=1000&sort=name&dir=asc`, { headers:{'Accept':'application/json'} });
          const json = await res.json();
          this.depts = json.data || [];
        }catch{}
      },
      qs(page){
        const p = new URLSearchParams();
        if(this.filters.q)          p.set('q', this.filters.q);
        if(this.filters.department) p.set('department', this.filters.department);
        if(this.filters.active !== '') p.set('active', this.filters.active);
        if(this.filters.from)       p.set('from', this.filters.from);
        if(this.filters.to)         p.set('to', this.filters.to);
        p.set('sort', this.filters.sort);
        p.set('dir',  this.filters.dir);
        p.set('per_page', this.filters.per_page);
        p.set('page', page);
        return p.toString();
      },
      async load(page){
        this.loading = true;
        try{
          const res = await fetch(`${this.apiBase}?${this.qs(page)}`, { headers:{'Accept':'application/json'} });
          const json = await res.json();
          this.items = json.data || [];
          this.meta  = json.meta || {};
        } finally {
          this.loading = false;
        }
      },
      go(page){ if(!this.meta || page < 1 || page > (this.meta.last_page || 1)) return; this.load(page); },
      applyFilters(){ this.load(1); },
      async open(id){
        const res = await fetch(`${this.apiBase}/${id}`, { headers:{'Accept':'application/json'} });
        const json = await res.json();
        const e = json.data || json;
        this.detail = e;
        this.showModal = true;
      },
      formatDate(iso){ try{ return iso ? new Date(iso).toLocaleString() : ''; } catch { return iso ?? ''; } }
    }
  }
  </script>
</x-layout>
