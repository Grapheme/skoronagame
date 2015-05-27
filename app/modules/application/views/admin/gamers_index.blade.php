@extends(Helper::acclayout())
@section('content')
    <h1 class="top-module-menu"><a href="{{ Request::path() }}">Игроки</a></h1>
    @if($gamers->count())
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <table class="table table-striped table-bordered min-table white-bg">
                    <thead>
                    <tr>
                        <th class="text-center" style="width:40px">#</th>
                        <th class="text-center">Имя</th>
                        <th class="text-center">E-mail</th>
                        <th class="text-center">Текущее кол-во баллов рейтинга</th>
                        <th class="text-center">Текущее кол-во побед</th>
                        <th class="text-center">Общее кол-во баллов рейтинга</th>
                        <th class="text-center">Общее кол-во побед</th>
                        <th class="text-center">Общее кол-во игр</th>
                        <th class="text-center">Лог последнего боя</th>
                        <th class="text-center"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($gamers as $index => $gamer)
                        <tr>
                            <?php $sub_index = Input::has('page') ? (int)Input::get('page')-1 : 0;?>
                            <td>{{ ($index+1)+($sub_index*50) }}</td>
                            <td>{{ $gamer->name }}</td>
                            <td>{{ $gamer->email }}</td>
                            <td>{{ $gamer->user_rating }}</td>
                            <td>{{ $gamer->user_winners }}</td>
                            <td>{{ $gamer->user_total_rating }}</td>
                            <td>{{ $gamer->user_total_winners }}</td>
                            <td>{{ $gamer->user_total_games }}</td>
                            <td><a href="javascript:void(0);" target="_blank">Просмотр</a></td>
                            <td class="text-center" style="white-space:nowrap;">
                                @if(Allow::action('game','delete'))
                                    {{ Form::open(array('route'=>array('gamer.destroy',$gamer->id),'method'=>'delete','style'=>'display:inline-block')) }}
                                    <button type="button" class="btn btn-danger remove-gamer"><i class="fa fa-trash-o"></i></button>
                                    {{ Form::close() }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                {{ $gamers->links() }}
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <div class="ajax-notifications custom">
                    <div class="alert alert-transparent">
                        <h4>Список пуст</h4>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop
@section('scripts')
    <script>
        var essence = 'gamer';
        var essence_name = 'игрока';
    </script>
    {{ HTML::script('private/js/modules/standard.js') }}
@stop
