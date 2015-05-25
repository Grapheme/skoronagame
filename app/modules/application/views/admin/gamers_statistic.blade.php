@extends(Helper::acclayout())
@section('content')
    <h1 class="top-module-menu"><a href="{{ Request::path() }}">Статистика</a></h1>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <table class="table table-striped table-bordered min-table white-bg">
                <tbody>
                    <tr>
                        <td>Число регистраций</td>
                        <td>{{ $req_count }}</td>
                    </tr>
                    <tr>
                        <td>Среднее время, проведенное в игре</td>
                        <td>{{ $game_medium_time }}</td>
                    </tr>
                    <tr>
                        <td>Среднее время ответов</td>
                        <td>{{ $question_medium_time }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@stop
@section('scripts')
@stop
