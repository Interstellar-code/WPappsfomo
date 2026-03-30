const LinksyDataTable = ({
    name: 'DataTable',
    props: {
        columns: {
            type: Array,
            required: true
        },
        data: {
            type: Array,
            default: []
        },

        loading: Boolean,

        selectableRows: {
            type: Boolean,
            default: false
        },

        expandableRows: {
            type: Boolean,
            default: false
        },
     },
    data() {
        return {
            checked: false,
            rowsMeta: [],
            columnsMeta: []
        }
    },
    computed: {
        nColspan: {
            get() {
                return this.columns.length + (this.selectableRows? 1 : 0);
            },
        },
    },
    watch: {
        data: {
            deep: true,
            handler(val){
                this.rowsMeta = val.map(e => ({}));
            },
        },
        checked: function(val) {
            this.rowsMeta = this.rowsMeta.map(e => ({
                ...e,
                selected: val? true : e.selected
            }));
        },
    },
    methods: {
        getRowColumnValue(column, item, index) {
            let value = null;
            if (column.selector) {
                value = item[column.selector];
            }

            if (column.format) {
                value = column.format(item, index);
            }

            return value;
        },
        onColumnClicked(column, index) {
            if (column.sortable) {
                const sortDirection = this.columnsMeta[index].sortDirection;
                this.columnsMeta[index].sortDirection = !sortDirection || sortDirection === 'desc'? 'asc' : 'desc';

                this.$emit('onSort', index, this.columnsMeta[index].sortDirection);
            }
        },
        onRowClicked(row, index) {
            this.$emit('onRowClicked', row, index);
        },
        onRowSelected(row, index) {
            this.rowsMeta[index].selected = !this.rowsMeta[index].selected;
            
            if (!this.rowsMeta[index].selected) {
                this.checked = false;
            }

            this.$emit('onRowSelected', row, index);
        },
    },
    template: `
    <div class="linksy-datatable">
        <div class="table">
            <table class="shadow">
                <thead>
                    <tr>
                        <td v-if="selectableRows" width="20" style="padding-right: 10px;">
                            <input type="checkbox" v-model="checked" />
                        </td>
                        <td
                            v-for="(column, index) in columns"
                            :title="column.title"
                            @click="onColumnClicked(column, index)"
                            :class="[{'sortable clickable': column.sortable}]"
                        >
                            <div>
                                <slot :name="'column-header-'+index" :item="column" :index="index">
                                    {{column.name}}
                                </slot>

                                <div v-if="column.sortable" class="sortable-container">
                                    <i class="fa fa-sort-up" :class="{'active': columnsMeta[index]?.sortDirection === 'asc'}"></i>
                                    <i class="fa fa-sort-down" :class="{'active': columnsMeta[index]?.sortDirection === 'desc'}"></i>
                                </div>
                            </div>
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in data">
                        <td v-if="selectableRows">
                            <input type="checkbox" :checked="rowsMeta[index]?.selected" @change="onRowSelected(item, index)" />
                        </td>
                        <td
                            v-for="(column, columnIndex) in columns"
                            :class="{sortable: column.sortable}"
                            @click="onRowClicked(item, index)"
                        >
                            <slot :name="'column-'+columnIndex" :item="item" :index="index">
                                {{getRowColumnValue(column, item, index)}}
                            </slot>
                        </td>
                    </tr>

                    <tr v-if="data.length < 1 && !loading">
                        <td :colspan="nColspan">
                            <span>Nothing to display</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="loader" v-if="loading">
            <span>
                <i class="fa fa-spin fa-spinner"></i>
            </span>
        </div>
    </div>
    `,
    beforeMount() {
        this.columnsMeta = this.columns.map((e,i) => {
            const meta = {};
            if (e.sortable) {
                meta.sort = true;
                meta.sortDirection = e.sortDirection;
            }

            return meta;
        });
    }
});