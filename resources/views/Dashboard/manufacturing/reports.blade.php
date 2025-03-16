@extends('layouts.admin')

@section('title', trans('admin.Manufacturing Reports'))

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
@endsection

@section('content')
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('admin.Manufacturing Reports') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{route('dashboard.home')}}">{{ trans('admin.Home') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ trans('admin.Manufacturing Reports') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <!-- Summary Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $totalRecipes }}</h3>
                            <p>{{ trans('admin.Total Recipes') }}</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $monthlyProduction }}</h3>
                            <p>{{ trans('admin.Monthly Production') }}</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-industry"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ number_format($averageWastage, 2) }}%</h3>
                            <p>{{ trans('admin.Average Wastage') }}</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-trash"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>{{ number_format($totalProductionCost, 2) }}</h3>
                            <p>{{ trans('admin.Total Production Cost') }}</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-money-bill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Section -->
            <div class="row">
                <!-- Recipe Cost Report -->
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ trans('admin.Recipe Cost Report') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>{{ trans('admin.Select Recipe') }}</label>
                                <select class="form-control" id="recipe-select">
                                    @foreach($recipes as $recipe)
                                        <option value="{{ $recipe->id }}">{{ $recipe->finalProduct->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ trans('admin.Date Range') }}</label>
                                <input type="text" class="form-control" id="recipe-cost-daterange">
                            </div>
                            <canvas id="recipe-cost-chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Production Performance -->
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ trans('admin.Production Performance') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>{{ trans('admin.Select Branch') }}</label>
                                <select class="form-control" id="branch-select">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ trans('admin.Select Period') }}</label>
                                <input type="month" class="form-control" id="performance-period">
                            </div>
                            <canvas id="performance-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wastage Report -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ trans('admin.Wastage Report') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ trans('admin.Production Line') }}</label>
                                        <select class="form-control" id="production-line-select">
                                            @foreach($productionLines as $line)
                                                <option value="{{ $line->id }}">{{ $line->production_line_code }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ trans('admin.Date Range') }}</label>
                                        <input type="text" class="form-control" id="wastage-daterange">
                                    </div>
                                </div>
                            </div>
                            <canvas id="wastage-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Material Usage -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ trans('admin.Material Usage') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ trans('admin.Raw Material') }}</label>
                                        <select class="form-control" id="raw-material-select">
                                            @foreach($rawMaterials as $material)
                                                <option value="{{ $material->id }}">{{ $material->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ trans('admin.Period') }}</label>
                                        <input type="month" class="form-control" id="material-usage-period">
                                    </div>
                                </div>
                            </div>
                            <canvas id="material-usage-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        // Initialize date pickers
        $('#recipe-cost-daterange, #wastage-daterange').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

        // Recipe Cost Chart
        let recipeCostChart = null;
        function loadRecipeCostReport() {
            const recipe_id = $('#recipe-select').val();
            const dates = $('#recipe-cost-daterange').val().split(' - ');
            
            $.get('{{ route("dashboard.manufacturing.recipe-cost-report") }}', {
                recipe_id: recipe_id,
                start_date: dates[0],
                end_date: dates[1]
            }, function(data) {
                if (recipeCostChart) {
                    recipeCostChart.destroy();
                }

                recipeCostChart = new Chart($('#recipe-cost-chart'), {
                    type: 'line',
                    data: {
                        labels: data.map(item => item.production_date),
                        datasets: [{
                            label: 'Production Cost',
                            data: data.map(item => item.total_cost),
                            borderColor: 'rgb(75, 192, 192)'
                        }]
                    }
                });
            });
        }

        // Production Performance Chart
        let performanceChart = null;
        function loadProductionPerformance() {
            const branch_id = $('#branch-select').val();
            const period = $('#performance-period').val();

            $.get('{{ route("dashboard.manufacturing.production-performance") }}', {
                branch_id: branch_id,
                period: period
            }, function(data) {
                if (performanceChart) {
                    performanceChart.destroy();
                }

                performanceChart = new Chart($('#performance-chart'), {
                    type: 'bar',
                    data: {
                        labels: data.map(item => item.production_line_code),
                        datasets: [{
                            label: 'Total Production',
                            data: data.map(item => item.total_production),
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgb(75, 192, 192)',
                            borderWidth: 1
                        }]
                    }
                });
            });
        }

        // Wastage Chart
        let wastageChart = null;
        function loadWastageReport() {
            const production_line_id = $('#production-line-select').val();
            const dates = $('#wastage-daterange').val().split(' - ');

            $.get('{{ route("dashboard.manufacturing.wastage-report") }}', {
                production_line_id: production_line_id,
                start_date: dates[0],
                end_date: dates[1]
            }, function(data) {
                if (wastageChart) {
                    wastageChart.destroy();
                }

                wastageChart = new Chart($('#wastage-chart'), {
                    type: 'line',
                    data: {
                        labels: data.map(item => item.production_date),
                        datasets: [{
                            label: 'Wastage Rate (%)',
                            data: data.map(item => item.wastage_rate),
                            borderColor: 'rgb(255, 99, 132)'
                        }]
                    }
                });
            });
        }

        // Material Usage Chart
        let materialUsageChart = null;
        function loadMaterialUsage() {
            const raw_material_id = $('#raw-material-select').val();
            const period = $('#material-usage-period').val();

            $.get('{{ route("dashboard.manufacturing.material-usage") }}', {
                raw_material_id: raw_material_id,
                period: period
            }, function(data) {
                if (materialUsageChart) {
                    materialUsageChart.destroy();
                }

                materialUsageChart = new Chart($('#material-usage-chart'), {
                    type: 'line',
                    data: {
                        labels: data.map(item => item.usage_date),
                        datasets: [{
                            label: 'Total Usage',
                            data: data.map(item => item.total_usage),
                            borderColor: 'rgb(153, 102, 255)'
                        }]
                    }
                });
            });
        }

        // Event listeners
        $('#recipe-select, #recipe-cost-daterange').change(loadRecipeCostReport);
        $('#branch-select, #performance-period').change(loadProductionPerformance);
        $('#production-line-select, #wastage-daterange').change(loadWastageReport);
        $('#raw-material-select, #material-usage-period').change(loadMaterialUsage);

        // Initial load
        $(document).ready(function() {
            loadRecipeCostReport();
            loadProductionPerformance();
            loadWastageReport();
            loadMaterialUsage();
        });
    </script>
@endsection