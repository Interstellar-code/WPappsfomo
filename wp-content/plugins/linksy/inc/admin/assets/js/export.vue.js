const Styles = {
    container: {
        border: 'none',
        color: '#FFFFFF',
        padding: '8px 11px',
        background: '#007AFF',
        position: 'relative',
        borderRadius: '5px'
    },
    actionsContainer: {
        paddingTop: '5px',
        paddingLeft: '14px',
        paddingRight: '10px',
        paddingBottom: '10px'
    },
    actions: {
        margin: 0,
        padding: '10px 0',
        width: '100%',
        display: 'flex',
        height: 'fit-content',
        alignItems: 'center',
        justifyContent: 'space-between'
    }
};
  
const LinksyExportHelpers = {
    csv: (data, headers) => {
        const contentHeader = (headers ? `${headers.join(',')}\n` : '');
        const content = `${contentHeader}${data.map((row) => {
            const items = [];
            const rowLength = headers? headers.length : Object.keys(row).length;

            if (typeof row === 'object') {
                items.push(Object.keys(row).slice(0, rowLength).map(key =>
                    `${row[key]}`.replace(new RegExp(escapeRegExp(','), 'g'), '.')
                ).join(','))
            }

            return items.join(',');
        }).join('\n')}`;
      
        return {
            content,
            type: 'text/csv',
            name: `${document.title}-${Date.now()}.csv`,
        };
    },
    excel: (data, headers) => {
        const contentHeader = (headers ? `<thead><tr><td>${headers.join('</td><td>')}</td></tr></thead>` : '');
        const contentBody = data.map(row => {
            const items = [];
            const rowLength = headers? headers.length : Object.keys(row).length;

            if (typeof row === 'object') {
                Object.keys(row).slice(0, rowLength).map(key => items.push(row[key]) );
            }
            return `<tr><td>${items.join('</td><td>')}</td></tr>`;
        });
        let content = `<table>${contentHeader}<tbody>${contentBody.join('')}</tbody></table>`;
        content = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table>${content}</table></body></html>`

        return {
            content: content,
            type: 'application/vnd.ms-excel',
            name: `${document.title}-${Date.now()}.xlsx`,
        };
    },
    print: (data, headers) => {
        const { content } = excel(data, headers);
      
        const style = '\n' +
            'body, table { \n' +
            'font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; \n' +
            'font-size:12px \n' +
            '}\n' +
            'table {\n' +
            'width: 100%;\n' +
            '}\n' +
            'thead {\n' +
            'font-weight: bold;\n' +
            '}';
        return `<style>${style}</style>${content}`;
    },
    log: (data, headers) => {
        const contentHeader = (headers ? `${headers.join(',')}\n` : '');
        const content = data.map((row) => {
            const items = [];
            const rowLength = headers? headers.length : Object.keys(row).length;

            if (typeof row === 'object') {
                items.push(Object.keys(row).slice(0, rowLength).map(key =>
                    `${row[key]}`.replace(new RegExp(escapeRegExp(','), 'g'), '.')
                ))
            }

            return items;
        });

        console.log(contentHeader);
        console.log(content)
    },
    create: (type, headers, data) => {
        switch (type) {
            case 'csv':
                return LinksyExportHelpers.csv(data, headers);

            case 'excel':
                return LinksyExportHelpers.excel(data, headers);

            case 'log':
                return LinksyExportHelpers.log(data, headers);
        
            default:
                console.error(`${type} not found`);
                break;
        }
    }
};

const LinksyExport = ({
    name: 'Export',
    props: {
        style: [
            String,
            Object
        ],
        loading: Boolean,
        columnTitle: {
            type: String,
            default: 'All'
        },
        columns: {
            type: Array,
            default: []
        },
    },
    data() {
        return {
            active: false,
            styles: Styles,
            checked: true,
            columnsMeta: []
        }
    },
    computed: {
        canExport: {
            get() {
                return this.columnsMeta.filter(e => e.selected).length > 0;
            }
        },
        validColumns: {
            get() {
                return this.columns.filter(e => e != null);
            }
        },
        selectedColumns: {
            get() {
                return this.columns.filter((c) => this.columnsMeta.find(m => m.name == c)?.selected);
            }
        },
    },
    watch: {
        columns: {
            deep: true,
            handler(){
                const selectedColumns = this.selectedColumns;
                this.columnsMeta = this.validColumns.map((item) => ({
                    name: item,
                    selected: selectedColumns.includes(item) || false
                }));
            },
        },
        loading: {
            deep: true,
            handler(val){
                if (val) {
                    this.active = false;
                }
            },
        },
    },
    methods: {
        onColumnsSelected() {
            this.checked = !this.checked;

            this.columnsMeta = this.columnsMeta.map(e => ({
                ...e,
                selected: this.checked
            }));
        },
        onColumnSelected(index) {
            if (this.checked && this.columnsMeta[index].selected) {
                this.checked = false;
            }
            this.columnsMeta[index].selected = !this.columnsMeta[index].selected;
        },

        onExport(type) {
            this.active = false;
            this.$emit('onExport', type, this.selectedColumns)
        }
    },
    template: `
        <div class="linksy-export" style="position: relative; display: inline-block;">
            <button style="margin: 0;" :style="[styles.container, style]" :disabled="this.validColumns.length < 1 || loading" @click="active = !active">
                <slot>
                    <i class="fa fa-file-export"></i>
                    <span>&nbsp;Export</span>
                </slot>
            </button>

            <div v-if="active && !loading" class="shadow" style="position:absolute;right:0;z-index:2;margin-top:5px;min-width:calc(100% - 20px);background:#FFFFFF;border-radius:3px;overflow-x:hidden;">
                <ul style="margin: 0;padding:2px 14px;">
                    <li style="padding: 8px 0; margin: 0;">
                        <input type="checkbox" :checked="checked" @change="onColumnsSelected"/>
                        <span>{{columnTitle}}</span>
                    </li>
                    <li v-for="(column, index) in validColumns" style="padding: 8px 0; margin: 0;">
                        <input type="checkbox" :checked="columnsMeta.find(m => m.name==column)?.selected" @change="onColumnSelected(index)" />
                        <span style="text-transform: capitalize;">{{column}}</span>
                    </li>
                </ul>

                <hr />

                <div :style="styles.actionsContainer">
                    <slot name="actions" :style="styles.actionsContainer" :disabled="!canExport" :columns="selectedColumns">
                        <button class="btn-link" :style="styles.actions" :disabled="!canExport" @click="onExport('csv')">
                            Export to CSV&nbsp;
                            <i class="fa fa-chevron-right" style="font-size: 12px;"></i>
                        </button>
                        <button class="btn-link" :style="styles.actions" :disabled="!canExport" @click="onExport('excel')">
                            Export to Excel&nbsp;
                            <i class="fa fa-chevron-right" style="font-size: 12px;"></i>
                        </button>
                    </slot>
                </div>
            </div>
        </div>
    `,
    beforeMount() {
        this.columnsMeta = this.validColumns.map((item) => ({
            name: item,
            selected: true
        }));
    },
});