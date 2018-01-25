<?php

include dirname(__FILE__) . '/when/When.php';

class When_api {

    public function process_rec_programs($start_date, $end_date, $repeat_programs) {
        $success = array('collision' => false);
        foreach ($repeat_programs as $program) {
            $program_start_date = $program['start_date'];
            $program_end_date = $program['end_date'];
            $rec_arr = explode("_", $program['rec_type']);
            $type = $rec_arr[0];
            $count = (int) $rec_arr[1];
            $day = (int) $rec_arr[2];
            $count2 = (int) $rec_arr[3];
            $days_extra = explode("#", $rec_arr[4]);
            $days = $days_extra[0];
            $extra = (int) $days_extra[1];
            $event_length = (int) $program['event_length'];
            $occurrences = '';

            syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs: start_date: " . print_r($start_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs: end_date: " . print_r($end_date, true));

            if ($type === 'day') {
                $occurrences = $this->day($program_start_date, $program_end_date, $end_date, $count, $event_length, $extra);
            } else if ($type === 'week') {
                $occurrences = $this->week($program_start_date, $program_end_date, $end_date, $count, $event_length, $days, $extra);
            } else if ($type === 'month') {
                $occurrences = $this->month($program_start_date, $program_end_date, $end_date, $count, $event_length, $day, $count2, $extra);
            } else if ($type === 'year') {
                $occurrences = $this->year($program_start_date, $program_end_date, $count, $event_length, $day, $count2, $extra);
            }
            syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs1: " . print_r($occurrences, true));

            foreach ($occurrences as $occurrence) {
                $occurrence_start_date = $occurrence['start_date'];
                $occurrence_end_date = $occurrence['end_date'];
                $collision = $this->datesOverlap($start_date, $end_date, $occurrence_start_date, $occurrence_end_date);
                syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs2: " . print_r($collision, true));
                if ($collision) {
                    $success = array('collision' => true);
                    break;
                }
            }
        }
        return $success;
    }

    public function process_non_rec_programs($start_date, $end_date, $non_repeat_programs) {
        $success = array('collision' => false);
        foreach ($non_repeat_programs as $program) {
            $program_start_date = $program['start_date'];
            $program_end_date = $program['end_date'];
            $collision = $this->datesOverlap($start_date, $end_date, $program_start_date, $program_end_date);
            syslog(LOG_NOTICE, "SMH DEBUG : process_non_rec_programs: " . print_r($collision, true));
            if ($collision) {
                $success = array('collision' => true);
                break;
            }
        }
        return $success;
    }

    public function day($program_start_date, $program_end_date, $end_date, $count, $event_length, $extra) {
        $r = new When();
        if ($program_end_date === '9999-02-01 08:00:00') {
            $r->startDate(new DateTime($program_start_date))
                    ->freq("daily")
                    ->interval($count)
                    ->until(new DateTime($end_date . ' +1 day'))
                    ->generateOccurrences();
        } else {
            if ($extra) {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("daily")
                        ->interval($count)
                        ->count($extra)
                        ->until(new DateTime($program_end_date))
                        ->generateOccurrences();
            } else {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("daily")
                        ->interval($count)
                        ->until(new DateTime($program_end_date))
                        ->generateOccurrences();
            }
        }

        $programs = array();
        foreach ($r->occurrences as $occurr) {
            $start_date = $occurr->format('Y-m-d H:i:s');

            $dateinsec = strtotime($start_date);
            $newdate = $dateinsec + $event_length;
            $end_date = date('Y-m-d H:i:s', $newdate);
            array_push($programs, array('start_date' => $start_date, 'end_date' => $end_date));
        }

        return $programs;
    }

    public function week($program_start_date, $program_end_date, $end_date, $count, $event_length, $days, $extra) {
        $days_explode = array_map('intval', explode(',', $days));
        $days_arr = array();
        foreach ($days_explode as $day) {
            if ($day === 0) {
                array_push($days_arr, 'SU');
            } else if ($day === 1) {
                array_push($days_arr, 'MO');
            } else if ($day === 2) {
                array_push($days_arr, 'TU');
            } else if ($day === 3) {
                array_push($days_arr, 'WE');
            } else if ($day === 4) {
                array_push($days_arr, 'TH');
            } else if ($day === 5) {
                array_push($days_arr, 'FR');
            } else if ($day === 6) {
                array_push($days_arr, 'SA');
            }
        }

        $r = new When();
        if ($program_end_date === '9999-02-01 08:00:00') {
            syslog(LOG_NOTICE, "SMH DEBUG : week: end_date: " . print_r($end_date, true));
            $r->startDate(new DateTime($program_start_date))
                    ->freq("weekly")
                    ->interval($count)
                    ->until(new DateTime($end_date . ' +1 day'))
                    ->byday($days_arr)
                    ->generateOccurrences();
        } else {
            if ($extra) {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("weekly")
                        ->interval($count)
                        ->count($extra)
                        ->byday($days_arr)
                        ->until(new DateTime($program_end_date))
                        ->generateOccurrences();
            } else {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("weekly")
                        ->interval($count)
                        ->byday($days_arr)
                        ->until(new DateTime($program_end_date))
                        ->generateOccurrences();
            }
        }

        $programs = array();
        foreach ($r->occurrences as $occurr) {
            $start_date = $occurr->format('Y-m-d H:i:s');

            $dateinsec = strtotime($start_date);
            $newdate = $dateinsec + $event_length;
            $end_date = date('Y-m-d H:i:s', $newdate);
            array_push($programs, array('start_date' => $start_date, 'end_date' => $end_date));
        }

        return $programs;
    }

    public function month($program_start_date, $program_end_date, $end_date, $count, $event_length, $day, $count2, $extra) {
        $r = new When();
        $on = '';
        if ($day === 0) {
            $on = $count2 . 'SU';
        } else if ($day === 1) {
            $on = $count2 . 'MO';
        } else if ($day === 2) {
            $on = $count2 . 'TU';
        } else if ($day === 3) {
            $on = $count2 . 'WE';
        } else if ($day === 4) {
            $on = $count2 . 'TH';
        } else if ($day === 5) {
            $on = $count2 . 'FR';
        } else if ($day === 6) {
            $on = $count2 . 'SA';
        }
        if ($program_end_date === '9999-02-01 08:00:00') {
            if ($day) {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("monthly")
                        ->byday($on)
                        ->interval($count)
                        ->count(10)
                        ->generateOccurrences();
            } else {
                $r->startDate(new DateTime($program_start_date, new DateTimeZone('UTC')))
                        ->freq("monthly")
                        ->interval($count)
                        ->until(new DateTime($end_date . ' +1 month', new DateTimeZone('UTC')))
                        ->generateOccurrences();
            }
        } else {
            if ($extra) {
                if ($day) {
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("monthly")
                            ->byday($on)
                            ->interval($count)
                            ->count($extra)
                            ->until(new DateTime($program_end_date))
                            ->generateOccurrences();
                } else {
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("monthly")
                            ->interval($count)
                            ->count($extra)
                            ->until(new DateTime($program_end_date))
                            ->generateOccurrences();
                }
            } else {
                if ($day) {
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("monthly")
                            ->byday($on)
                            ->interval($count)
                            ->until(new DateTime($program_end_date))
                            ->generateOccurrences();
                } else {
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("monthly")
                            ->interval($count)
                            ->until(new DateTime($program_end_date))
                            ->generateOccurrences();
                }
            }
        }

        $programs = array();
        foreach ($r->occurrences as $occurr) {
            $start_date = $occurr->format('Y-m-d H:i:s');

            $dateinsec = strtotime($start_date);
            $newdate = $dateinsec + $event_length;
            $end_date = date('Y-m-d H:i:s', $newdate);
            array_push($programs, array('start_date' => $start_date, 'end_date' => $end_date));
        }

        return $programs;
    }

    public function year($start_date, $end_date, $count, $event_length, $day, $count2, $extra) {
        $r = new When();
        $on = '';
        if ($day === 0) {
            $on = $count2 . 'SU';
        } else if ($day === 1) {
            $on = $count2 . 'MO';
        } else if ($day === 2) {
            $on = $count2 . 'TU';
        } else if ($day === 3) {
            $on = $count2 . 'WE';
        } else if ($day === 4) {
            $on = $count2 . 'TH';
        } else if ($day === 5) {
            $on = $count2 . 'FR';
        } else if ($day === 6) {
            $on = $count2 . 'SA';
        }
        if ($end_date === '9999-02-01 08:00:00') {
            if ($day) {
                $r->startDate(new DateTime($start_date))
                        ->freq("yearly")
                        ->byday($on)
                        ->interval($count)
                        ->count(10)
                        ->generateOccurrences();
            } else {
                $r->startDate(new DateTime($start_date))
                        ->freq("yearly")
                        ->interval($count)
                        ->count(10)
                        ->generateOccurrences();
            }
        } else {
            if ($extra) {
                if ($day) {
                    $r->startDate(new DateTime($start_date))
                            ->freq("yearly")
                            ->byday($on)
                            ->interval($count)
                            ->count($extra)
                            ->until(new DateTime($end_date))
                            ->generateOccurrences();
                } else {
                    $r->startDate(new DateTime($start_date))
                            ->freq("yearly")
                            ->interval($count)
                            ->count($extra)
                            ->until(new DateTime($end_date))
                            ->generateOccurrences();
                }
            } else {
                if ($day) {
                    $r->startDate(new DateTime($start_date))
                            ->freq("yearly")
                            ->byday($on)
                            ->interval($count)
                            ->until(new DateTime($end_date))
                            ->generateOccurrences();
                } else {
                    $r->startDate(new DateTime($start_date))
                            ->freq("yearly")
                            ->interval($count)
                            ->until(new DateTime($end_date))
                            ->generateOccurrences();
                }
            }
        }

        $programs = array();
        foreach ($r->occurrences as $occurr) {
            $start_date = $occurr->format('Y-m-d H:i:s');

            $dateinsec = strtotime($start_date);
            $newdate = $dateinsec + $event_length;
            $end_date = date('Y-m-d H:i:s', $newdate);
            array_push($programs, array('start_date' => $start_date, 'end_date' => $end_date));
        }

        return $programs;
    }

    public function datesOverlap($start_one, $end_one, $start_two, $end_two) {
        $start_date_one = new DateTime($start_one);
        $end_date_one = new DateTime($end_one);
        $start_date_two = new DateTime($start_two);
        $end_date_two = new DateTime($end_two);

        if ($start_date_one <= $end_date_two && $end_date_one >= $start_date_two) { //If the dates overlap
            return min($end_date_one, $end_date_two)->diff(max($start_date_two, $start_date_one))->days + 1; //return how many days overlap
        }

        return 0; //Return 0 if there is no overlap
    }

}

?>