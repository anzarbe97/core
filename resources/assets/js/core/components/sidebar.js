import Button from './sidebarButton';
import Events from '../events';
import Keyboard from '../keyboard';

/**
 * A collapsible sidebar that can show different content "tabs"
 *
 * @type {Object}
 */
export default {
    template: `<aside class="sidebar" :class="classObject">
        <div class="sidebar__buttons" v-if="showButtons">
            <sidebar-button
                v-for="tab in tabs"
                :tab="tab"
                :key="tab.id"
                :direction="direction"
                ></sidebar-button>
        </div>
        <div class="sidebar__tabs"><slot></slot></div>
    </aside>`,
    components: {
        sidebarButton: Button,
    },
    data() {
        return {
            open: false,
            tabs: [],
            lastOpenedTab: null,
            tabIdSequence: 0,
        };
    },
    props: {
        openTab: {
            type: String
        },
        showButtons: {
            type: Boolean,
            default: true,
        },
        // Indicates whether the sidebar is on the 'left' or on the 'right'
        direction: {
            type: String,
            default: 'right',
            validator(value) {
                return value === 'left' || value === 'right';
            },
        },
        toggleOnKeyboard: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        classObject() {
            return {
                'sidebar--open': this.open,
                'sidebar--left': this.isLeft,
                'sidebar--right': !this.isLeft,
            };
        },
        isLeft() {
            return this.direction === 'left';
        },
    },
    methods: {
        registerTab(tab) {
            tab.id = this.tabIdSequence++;
            this.tabs.push(tab);
        },
        handleOpenTab(name) {
            this.open = true;
            this.lastOpenedTab = name;
            this.$emit('toggle', name);
            Events.$emit('sidebar.toggle', name);
            Events.$emit(`sidebar.open.${name}`);
        },
        handleCloseTab(name) {
            this.open = false;
            this.$emit('toggle', name);
            Events.$emit('sidebar.toggle', name);
            Events.$emit(`sidebar.close.${name}`);
        },
        toggleLastOpenedTab(e) {
            if (this.open) {
                e.preventDefault();
                this.$emit('close', this.lastOpenedTab);
            } else if (this.lastOpenedTab) {
                e.preventDefault();
                this.$emit('open', this.lastOpenedTab);
            } else if (this.tabs.length > 0) {
                e.preventDefault();
                this.$emit('open', this.tabs[0].name);
            }
        },
    },
    watch: {
        openTab(tab) {
            this.$emit('open', tab);
        },
    },
    created() {
        this.$on('open', this.handleOpenTab);
        this.$on('close', this.handleCloseTab);

        if (this.toggleOnKeyboard) {
            Keyboard.on('Tab', this.toggleLastOpenedTab);
        }
    },
    mounted() {
        if (this.openTab) {
            this.$emit('open', this.openTab);
        }
    }
};
