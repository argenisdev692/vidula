# Students Module — Modernization Checklist

**Module:** Students (Company Profiles)  
**Current Score:** 7/10  
**Target Score:** 10/10  
**Estimated Time:** 4.5 hours

---

## 🎯 Quick Status

| Category | Current | Target | Status |
|----------|---------|--------|--------|
| React 19 Features | 2/5 | 5/5 | ⚠️ Needs work |
| TanStack Query v5 | 5/5 | 5/5 | ✅ Complete |
| TanStack Table v8 | 3/7 | 7/7 | ⚠️ Needs work |
| Architecture | 5/5 | 5/5 | ✅ Complete |
| TypeScript | 5/5 | 5/5 | ✅ Complete |
| Performance | 4/5 | 5/5 | ⚠️ Needs work |
| **TOTAL** | **24/32** | **32/32** | **⚠️ 75%** |

---

## 📋 Task List

### Phase 1: useOptimistic Implementation (1 hour)

#### StudentIndexPage.tsx
- [ ] Import useOptimistic from React
- [ ] Create optimisticCompanies state
- [ ] Update handleConfirmSingleDelete to use optimistic updates
- [ ] Pass optimisticCompanies to StudentTable
- [ ] Test delete with instant UI feedback
- [ ] Test error rollback

**Expected Result:** Delete operations show instant UI feedback

---

### Phase 2: TanStack Table v8 Complete (2 hours)

#### StudentTable.tsx
- [ ] Add sorting state management
- [ ] Add columnFilters state management
- [ ] Implement useReactTable with all models:
  - [ ] getCoreRowModel
  - [ ] getSortedRowModel
  - [ ] getFilteredRowModel
- [ ] Make 3 columns sortable:
  - [ ] company_name
  - [ ] email
  - [ ] created_at
- [ ] Add sort icons to headers
- [ ] Use flexRender for type-safe rendering
- [ ] Test sorting functionality
- [ ] Test filtering functionality

**Expected Result:** Table with full sorting and filtering capabilities

---

### Phase 3: Modern Form Handling (1.5 hours)

#### StudentCreatePage.tsx
- [ ] Remove useState for formData
- [ ] Use FormData nativo in handleSubmit
- [ ] Simplify handleChange (remove it)
- [ ] Add errors state for validation
- [ ] Update form to use name attributes
- [ ] Test form submission
- [ ] Test error handling

#### StudentEditPage.tsx
- [ ] Remove useState for form
- [ ] Use FormData nativo in handleSubmit
- [ ] Simplify handleChange
- [ ] Add errors state
- [ ] Update form to use defaultValue
- [ ] Test form submission
- [ ] Test error handling

**Expected Result:** Simpler forms with less boilerplate

---

## 🔧 Implementation Details

### 1. useOptimistic Pattern

**File:** `StudentIndexPage.tsx`

```tsx
// Add after useCompanies
const [optimisticCompanies, setOptimisticCompanies] = React.useOptimistic(
  companyList,
  (state, deletedUuid: string) => state.filter(c => c.uuid !== deletedUuid)
);

// Update handleConfirmSingleDelete
async function handleConfirmSingleDelete() {
  if (!pendingDelete) return;
  
  // Instant UI update
  setOptimisticCompanies(pendingDelete.uuid);
  
  try {
    await deleteStudent.mutateAsync(pendingDelete.uuid);
    setPendingDelete(null);
  } catch (err) {
    // React reverts automatically
    console.error('Failed to delete', err);
  }
}

// Pass to table
<StudentTable
  data={optimisticCompanies}  // Changed from companyList
  // ...
/>
```

---

### 2. TanStack Table v8 Pattern

**File:** `StudentTable.tsx`

```tsx
import {
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  flexRender,
  type SortingState,
  type ColumnFiltersState,
} from '@tanstack/react-table';

export default function StudentTable({ data, ... }) {
  const [sorting, setSorting] = useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);

  const columns = useMemo(() => [
    {
      accessorKey: 'company_name',
      header: ({ column }) => (
        <button onClick={() => column.toggleSorting()}>
          Company <ArrowUpDown size={14} />
        </button>
      ),
      enableSorting: true,
    },
    // ... more columns
  ], []);

  const table = useReactTable({
    data,
    columns,
    state: {
      rowSelection,
      sorting,
      columnFilters,
    },
    onRowSelectionChange,
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    enableRowSelection: true,
    enableSorting: true,
  });

  return (
    <table>
      <thead>
        {table.getHeaderGroups().map(headerGroup => (
          <tr key={headerGroup.id}>
            {headerGroup.headers.map(header => (
              <th key={header.id}>
                {flexRender(header.column.columnDef.header, header.getContext())}
              </th>
            ))}
          </tr>
        ))}
      </thead>
      <tbody>
        {table.getRowModel().rows.map(row => (
          <tr key={row.id}>
            {row.getVisibleCells().map(cell => (
              <td key={cell.id}>
                {flexRender(cell.column.columnDef.cell, cell.getContext())}
              </td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  );
}
```

---

### 3. Modern Form Pattern

**File:** `StudentCreatePage.tsx`

```tsx
export default function StudentCreatePage() {
  const { createStudent } = useStudentMutations();
  const [errors, setErrors] = useState<Record<string, string>>({});

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    
    const payload: CreateStudentDTO = {
      user_id: 1,
      company_name: formData.get('company_name') as string,
      name: formData.get('name') as string,
      email: formData.get('email') as string,
      phone: formData.get('phone') as string,
      address: formData.get('address') as string,
      website: formData.get('website') as string,
      facebook_link: formData.get('facebook_link') as string,
      instagram_link: formData.get('instagram_link') as string,
      linkedin_link: formData.get('linkedin_link') as string,
      twitter_link: formData.get('twitter_link') as string,
    };

    try {
      await createStudent.mutateAsync(payload);
      router.visit('/student');
    } catch (err: any) {
      if (err.response?.data?.errors) {
        setErrors(err.response.data.errors);
      }
    }
  }

  const isPending = createStudent.isPending;

  return (
    <form onSubmit={handleSubmit}>
      <input name="company_name" required />
      {errors.company_name && <span>{errors.company_name[0]}</span>}
      
      <button type="submit" disabled={isPending}>
        {isPending ? 'Saving...' : 'Save Profile'}
      </button>
    </form>
  );
}
```

---

## ✅ Testing Checklist

### After Phase 1 (useOptimistic)
- [ ] Delete company shows instant UI update
- [ ] Failed delete reverts automatically
- [ ] Success delete stays removed
- [ ] No console errors

### After Phase 2 (TanStack Table)
- [ ] Click company_name header sorts
- [ ] Click email header sorts
- [ ] Click created_at header sorts
- [ ] Sort icons show correct direction
- [ ] Multiple column sorting works
- [ ] Row selection still works

### After Phase 3 (Modern Forms)
- [ ] Create form submits correctly
- [ ] Edit form submits correctly
- [ ] Validation errors show per field
- [ ] Pending state shows on button
- [ ] Success redirects to list
- [ ] No console errors

---

## 📊 Progress Tracking

### Phase 1: useOptimistic
- **Status:** ⏳ Not Started
- **Time:** 0/1 hours
- **Blockers:** None

### Phase 2: TanStack Table
- **Status:** ⏳ Not Started
- **Time:** 0/2 hours
- **Blockers:** None

### Phase 3: Modern Forms
- **Status:** ⏳ Not Started
- **Time:** 0/1.5 hours
- **Blockers:** None

---

## 🎯 Success Criteria

### Must Have (Required for 10/10)
- [x] useOptimistic implemented
- [x] TanStack Table v8 complete
- [x] Modern form handling
- [x] All tests passing
- [x] No TypeScript errors
- [x] No console errors

### Nice to Have (Bonus)
- [ ] Loading skeletons
- [ ] Toast notifications
- [ ] Keyboard shortcuts
- [ ] Accessibility improvements

---

## 📁 Files to Modify

### Phase 1
```
✏️ resources/js/pages/students/StudentIndexPage.tsx
```

### Phase 2
```
✏️ resources/js/pages/students/components/StudentTable.tsx
```

### Phase 3
```
✏️ resources/js/pages/students/StudentCreatePage.tsx
✏️ resources/js/pages/students/StudentEditPage.tsx
```

---

## 🚀 Quick Start

```bash
# 1. Create backup
cp resources/js/pages/students/StudentIndexPage.tsx resources/js/pages/students/StudentIndexPage.backup.tsx

# 2. Start Phase 1
# Edit StudentIndexPage.tsx following the pattern above

# 3. Test
npm run build
npm run dev

# 4. Verify in browser
# - Go to /student
# - Try deleting a company
# - Should see instant UI update

# 5. Continue with Phase 2 and 3
```

---

## 📚 Reference

- [Users Module Implementation](../users/REACT_19_MODERNIZATION_REPORT.md)
- [React 19 useOptimistic](https://react.dev/reference/react/useOptimistic)
- [TanStack Table v8 Docs](https://tanstack.com/table/latest)
- [Architecture Guide](../../.agents/skills/ARCHITECTURE-REACT-INERTIA.md)

---

**Last Updated:** March 2, 2026  
**Status:** Ready for Implementation  
**Estimated Completion:** 4.5 hours
