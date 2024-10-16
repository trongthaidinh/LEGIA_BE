<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function trackVisit()
    {
        $today = now()->toDateString();

        $visit = Visit::where('visit_date', $today)->first();

        if ($visit) {
            $visit->visit_count += 1;
            $visit->total_visits += 1;
            $visit->save();
        } else {
            $totalVisits = Visit::sum('visit_count');
            Visit::create([
                'visit_date' => $today,
                'visit_count' => 1,
                'total_visits' => $totalVisits + 1,
            ]);
        }

        return responseJson(
            null,
            200,
            'Visit tracked successfully'
        );
    }

    public function getVisitStats()
    {
        $today = now()->toDateString();

        $todayVisits = Visit::where('visit_date', $today)->first();

        $totalVisits = Visit::sum('visit_count');

        return responseJson(
            [
                'today_visits' => $todayVisits ? $todayVisits->visit_count : 0,
                'total_visits' => $totalVisits,
            ],
            200
        );
    }
}
