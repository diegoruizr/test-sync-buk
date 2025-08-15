<x-layouts.app :title="'Habilidades'">
  <div x-data="skillsPage()" x-init="init()" class="space-y-4">

    <h1 class="text-2xl font-semibold">Habilidades</h1>

    <form @submit.prevent="applyFilters" class="bg-white rounded-lg shadow p-4 grid grid-cols-1 md:grid-cols-6 gap-3">
      <input x-model="filters.q" type="text" placeholder="Buscar por nombre" class="md:col-span-2 border rounded px-3 py-2">
      <input x-model="filters.from" type="date" class="border rounded px-3 py-2">
      <input x-model="filters.to" type="date" class="border rounded px-3 py-2">
      <select x-model="filters.sort" class="border rounded px-3 py-2">
        <template x-for="field in sortFields" :key="field.value">
          <option :value="field.value" x-text="field.label"></option>
        </template>
      </select>
      <select x-model="filters.dir" class="border rounded px-3 py-2">
        <option value="desc">Descendente</option>
        <option value="asc">Ascendente</option>
      </select>
      <div class="md:col-span-6 flex items-center gap-3">
        <select x-model.number="filters.per_page" class="border rounded px-3 py-2">
          <template x-for="pp in [15,25,50,100]" :key="pp">
            <option :value="pp" x-text="pp + '/página'"></option>
          </template>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filtrar</button>
      </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 text-left">
            <tr>
              <th class="px-4 py-2 w-20">ID</th>
              <th class="px-4 py-2">Nombre</th>
              <th class="px-4 py-2">Nivel requerido</th>
              <th class="px-4 py-2">Creado</th>
              <th class="px-4 py-2">Actualizado</th>
              <th class="px-4 py-2 w-28"></th>
            </tr>
          </thead>
          <tbody>
            <template x-if="loading">
              <tr><td colspan="6" class="px-4 py-6 text-gray-500">Cargando...</td></tr>
            </template>

            <template x-if="!loading && items.length === 0">
              <tr><td colspan="6" class="px-4 py-6 text-gray-500">Sin resultados</td></tr>
            </template>

            <template x-for="s in items" :key="s.id">
              <tr class="border-t">
                <td class="px-4 py-2" x-text="s.id"></td>
                <td class="px-4 py-2" x-text="s.name"></td>
                <td class="px-4 py-2" x-text="s.level_required"></td>
                <td class="px-4 py-2" x-text="formatDate(s.created_at)"></td>
                <td class="px-4 py-2" x-text="formatDate(s.updated_at)"></td>
                <td class="px-4 py-2 text-right">
                  <button @click="open(s.id)" class="px-3 py-1 border rounded hover:bg-gray-50">Ver</button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

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

    <!-- Modal -->
    <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" style="display: none;">
      <div @click.outside="showModal=false" class="bg-white w-full max-w-2xl rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Skill</h2>
          <button @click="showModal=false" class="text-gray-500 hover:text-gray-700">✕</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm text-gray-600 mb-1">ID</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100" :value="detail.id" disabled>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Nombre</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100" :value="detail.name" disabled>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Nivel requerido</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100" :value="detail.level_required" disabled>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Actualizado</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100" :value="detail.updated_at" disabled>
          </div>
        </div>

        <div class="mt-4">
          <span class="inline-block px-2 py-1 text-xs rounded bg-gray-200 text-gray-700">Solo lectura</span>
        </div>

        <div class="mt-6 text-right">
          <button @click="showModal=false" class="px-4 py-2 border rounded hover:bg-gray-50">Cerrar</button>
        </div>
      </div>
    </div>

  </div>

  <script>
  function skillsPage(){
    return {
      apiBase: '/api/system-attendance/skills',
      items: [], meta: {}, loading: false,
      showModal: false,
      detail: { id:'', name:'', level_required:'', created_at:'', updated_at:'' },
      filters: { q:'', from:'', to:'', sort:'updated_at', dir:'desc', per_page:15 },
      sortFields: [
        { value: 'updated_at', label: 'Actualizado' },
        { value: 'created_at', label: 'Creado' },
        { value: 'name', label: 'Nombre' },
        { value: 'id', label: 'ID' }
      ],
      init(){ this.load(1); },
      qs(page){
        const p = new URLSearchParams();
        if(this.filters.q)    p.set('q', this.filters.q);
        if(this.filters.from) p.set('from', this.filters.from);
        if(this.filters.to)   p.set('to', this.filters.to);
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
        } finally { this.loading = false; }
      },
      go(page){ if(!this.meta || page<1 || page>(this.meta.last_page||1)) return; this.load(page); },
      applyFilters(){ this.load(1); },
      async open(id){
        const res = await fetch(`${this.apiBase}/${id}`, { headers:{'Accept':'application/json'} });
        const json = await res.json();
        const s = json.data || json;
        this.detail = {
          id: s.id ?? '', name: s.name ?? '',
          level_required: s.level_required ?? '',
          created_at: s.created_at ?? '', updated_at: s.updated_at ?? ''
        };
        this.showModal = true;
      },
      formatDate(iso){ try{ return iso ? new Date(iso).toLocaleString() : ''; } catch { return iso ?? ''; } }
    }
  }
  </script>
</x-layout>
