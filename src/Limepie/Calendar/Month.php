<?php declare(strict_types=1);

namespace Limepie\Calendar;

class Month
{
    public $days = [];

    // 달력에 들어갈 한달치 날짜정보를 만들어 리턴한다.
    public function getTable(\DateTime $start, \DateTime $end)
    {
        $first = (clone $start)->modify('first day of this month');
        $last  = (clone $end)->modify('last day of this month');

        $period = new \DatePeriod(
            $first,
            new \DateInterval('P1D'),
            (clone $last)->modify('+1 day') // include end date
        );

        // (1) 달력 앞쪽에 빈 셀을 채우기 위해 지난 달 날짜들을 넣는다.

        if (0 !== (int) $first->format('w')) {
            // 보여줄 달의 이전 달 정보를 가져온다.

            $prev = (clone $first)->modify('- ' . $first->format('w') . ' days');

            $prependPeriod = new \DatePeriod(
                $prev,
                new \DateInterval('P1D'),
                $first
            );

            foreach ($prependPeriod as $key => $value) {
                $this->days[] = [
                    'is_blank' => true,
                    'datetime' => $value,
                ];
            }
        }

        foreach ($period as $key => $value) {
            //\pr($value, $start, $value >= $start);
            $this->days[] = [
                'is_blank' => $value < $start && $value > $end,
                'datetime' => $value,
            ];
        }

        // (3) 빈 셀을 채우기 위한 다음 달 날짜를 넣는다.

        if (6 !== (int) $last->format('w')) {
            // 보여줄 달의 이전 달 정보를 가져온다.
            $appendPeriod = new \DatePeriod(
                (clone $last)->modify('+ 1 days'),
                new \DateInterval('P1D'),
                (clone $last)->modify('+ ' . ((6 - $last->format('w')) + 1) . ' days')
            );

            foreach ($appendPeriod as $key => $value) {
                $this->days[] = [
                    'is_blank' => true,
                    'datetime' => $value,
                ];
            }
        }

        // $this->days 배열요소들을 7개씩 묶은 다중배열을 만들어 리턴한다.
        return \array_chunk($this->days, 7);
    }

    public function getTable2(\DateTime $start, \DateTime $end)
    {
        $period0 = new \DatePeriod(
            $start,
            new \DateInterval('P1M'),
            (clone $end)->modify('+1 day') // include end date
        );

        $group = [];

        foreach ($period0 as $current) {
            $group[$current->format('Y-m')] = (new self)->getTable($current, $current);
        }
        // $this->days 배열요소들을 7개씩 묶은 다중배열을 만들어 리턴한다.
        return $group;
    }

    // 인자로 전달된 타임스탬프가 있는 달의 정보를 만들어 리턴한다.
    public function getMonth($stamp)
    {
        $year  = (int) \date('Y', $stamp);
        $month = (int) \date('n', $stamp);

        $monthstamp = \mktime(0, 0, 0, $month, 1, $year);
        $end_day    = (int) \date('t', $monthstamp);

        return [
            // 달 시작 시점의 타임스탬프
            'monthstamp' => $monthstamp,

            // 달의 마지막날 28~31
            'end_day' => $end_day,

            // 달 첫날의 요일 0~6
            'start_week' => (int) \date('w', $monthstamp),

            // 달 마지막날의 요일
            'end_week' => (int) \date('w', \mktime(0, 0, 0, $month, $end_day, $year)),
        ];
    }

    // 날짜별로 날짜와 제약조건을 담은 배열을 만들어 $this->days 배열에 넣는다.
    public function addDay($month, $day, $is_current_month = 0)
    {
        $this->days[] = [
            'month'   => $month,
            'day'     => $day,
            'is_curr' => $is_current_month,
        ];
    }
}
