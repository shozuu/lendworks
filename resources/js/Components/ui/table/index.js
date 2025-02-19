/**
 * Change Log - table/index.js
 * 
 * Changes Made:
 * 1. Converted from raw JavaScript components to Vue components
 * 2. Created modular table components structure:
 *    - Split into individual .vue components
 *    - Maintained shadcn-vue styling
 *    - Improved component reusability
 * 3. Centralized exports through index.js
 * 4. Improved TypeScript/IDE support
 * 
 * Note: This file now serves as the central export point for all table components
 * Previously, these components were defined in a single table.js file
 */

import Table from './Table.vue'
import TableHeader from './TableHeader.vue'
import TableBody from './TableBody.vue'
import TableHead from './TableHead.vue'
import TableRow from './TableRow.vue'
import TableCell from './TableCell.vue'

export {
    Table,
    TableHeader,
    TableBody,
    TableHead,
    TableRow,
    TableCell
}
