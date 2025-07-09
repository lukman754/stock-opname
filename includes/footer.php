</main>
</div>
</div>

<!-- Bootstrap JS Bundle dengan Popper dari CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery dari CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    // Global DataTables initialization
    function initializeDataTable(tableSelector, options = {}) {
        // Destroy existing DataTable instance if it exists
        if ($.fn.DataTable.isDataTable(tableSelector)) {
            $(tableSelector).DataTable().destroy();
        }

        // Default options
        const defaultOptions = {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
            },
            responsive: true,
            pageLength: 25,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            order: [], // Default no sorting
            columnDefs: [
                { orderable: false, targets: -1 } // Last column (actions) not sortable by default
            ],
            drawCallback: function () {
                // Reinitialize tooltips after table redraw
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        };

        // Merge default options with custom options
        const tableOptions = { ...defaultOptions, ...options };

        // Initialize DataTable
        if ($(tableSelector).length) {
            return $(tableSelector).DataTable(tableOptions);
        }
        return null;
    }

    // Initialize all tables with class 'datatable'
    $(document).ready(function () {
        $('.datatable').each(function () {
            const table = $(this);
            const defaultSortColumn = table.data('default-sort') || 0;
            const defaultSortOrder = table.data('default-order') || 'asc';

            initializeDataTable(table, {
                order: [[defaultSortColumn, defaultSortOrder]],
                columnDefs: [
                    { orderable: false, targets: -1 }, // Last column (actions) not sortable
                    { orderable: false, targets: 0 }   // First column (No) not sortable
                ]
            });
        });

        // Inisialisasi khusus untuk tabel stock opname detail
        try {
            if ($('#stock-opname-detail').length) {
                $('#stock-opname-detail').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                    },
                    ordering: false,  // Nonaktifkan pengurutan
                    searching: true,  // Aktifkan pencarian
                    paging: true,     // Aktifkan pagination
                    info: true,       // Tampilkan info
                    autoWidth: false  // Nonaktifkan auto width
                });
            }
        } catch (error) {
            console.error('Error initializing stock-opname-detail table:', error);
        }

        // Inisialisasi tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Auto hide alert setelah 5 detik
        setTimeout(function () {
            $('.alert').alert('close');
        }, 5000);

        // Mobile sidebar toggle functionality
        $('#sidebarToggler').on('click', function () {
            $('#sidebar').toggleClass('show');
            $('#sidebarBackdrop').toggleClass('show');
            $('body').toggleClass('overflow-hidden');
        });

        $('#sidebarBackdrop').on('click', function () {
            $('#sidebar').removeClass('show');
            $('#sidebarBackdrop').removeClass('show');
            $('body').removeClass('overflow-hidden');
        });

        // Close sidebar when clicking on a link (mobile only)
        $('#sidebar .nav-link').on('click', function () {
            if (window.innerWidth < 768) {
                $('#sidebar').removeClass('show');
                $('#sidebarBackdrop').removeClass('show');
                $('body').removeClass('overflow-hidden');
            }
        });

        // Handle window resize
        $(window).on('resize', function () {
            if (window.innerWidth >= 768) {
                $('#sidebar').removeClass('show');
                $('#sidebarBackdrop').removeClass('show');
                $('body').removeClass('overflow-hidden');
            }
        });

        // Add active class to parent accordion when child is active
        $('.nav-link.active').each(function () {
            $(this).closest('.collapse').addClass('show');
            $(this).closest('.nav-category')
                .find('.nav-category-header')
                .attr('aria-expanded', 'true');
        });
    });
</script>
</body>

</html>