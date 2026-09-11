@module('billing')
has-billing
@else
no-billing
@endmodule
@module('billing', 'reports')
has-both
@endmodule
@unlessmodule('reports')
missing-reports
@endmodule
@anymodule('billing', 'reports')
has-any
@endanymodule
@can('module', 'billing')
gate-billing
@endcan
