<?php

namespace App\Http\Controllers;

use App\Http\Requests\TableRequestCreateRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Request as TableRequest;
use Illuminate\Support\Facades\Auth;
use App\Repositories\TableRequestsRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

class TableAppController extends Controller
{
    private $tableRequestsRepository;
    private $userRepository;

   public function __construct()
    {
        $this->tableRequestsRepository = app(TableRequestsRepository::class);
        $this->userRepository = app(UserRepository::class);
    }

    public function index()
    {
        if (Auth::check()) {
            if (Auth::user()->role === 'user') {
                $id = $this->userRepository->getAuthUserId();
                // взять все записи конкретного пользователя
                $userRequests = TableRequest::all()->where('user_id', $id);

                return view('main', compact('userRequests'));
            } elseif (Auth::user()->role === 'admin') {
                $userRequests = TableRequest::all();

                return view('main', compact('userRequests'));
            }
        }

        return view('main');
    }

    public function create()
    {
        $userId = $this->userRepository->getAuthUserId();

        // узнать дату последнего запроса пользователя
        $lastRequest = $this->tableRequestsRepository->getLastTimeRequest($userId);

        if (isset($lastRequest)) {
            $currentDate = new Carbon('now', 'Europe/Minsk');

            $howDiffHours = $this->tableRequestsRepository->getDiffHours($currentDate, $lastRequest);

            if ($howDiffHours < 0) {
                return view('create');
            } else {
                redirect()->back()->send()
                    ->with(['danger' => 'Отклонена. Следующая заявка через ' . $howDiffHours . ' часов']);
            }
        }
        return view('create');
    }

    public function store(TableRequestCreateRequest $request)
    {
        // получение айди юзера
        $userId = $this->userRepository->getAuthUserId();

        // Сохранение файла и получение пути
        if (isset($request->image)) {
            $pathFile = $this->tableRequestsRepository
                ->saveImageOnStorage($request);
        } else {
            $pathFile = null;
        }

        // Сохранение
        $result = $this->tableRequestsRepository
            ->storeRequest($request, $userId, $pathFile);

        if ($result) {
            return redirect()->route('tableapp.index')
                ->with(['success' => 'Заявка успешно добавлена']);
        } else {
            return back()->withErrors(['msg' => 'Ошибка добавления заявки']);
        }
    }

    public function close($id)
    {
        $requestData = TableRequest::find($id);
        $requestData->status = 'Закрыто';

        //dd($requestData);
        $requestData->save();

        redirect()->back()->send();
    }
    public function answer($id)
    {
        $requestData = TableRequest::find($id);

        return view('answer', compact('requestData'));
    }

    public function response(Request $request)
    {
        $requestData = TableRequest::find($request->id);

        $requestData->response = $request->response;
        $requestData->status = 'Отвечено';
        $requestData->save();

        redirect()->route('tableapp.index')->send();
    }
}
