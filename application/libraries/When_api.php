<?php

include dirname(__FILE__) . '/when/When.php';

class When_api {

    public function process_non_rec_programs_a($start_date, $end_date, $rec_type, $event_length, $non_repeat_programs) {
        $success = array('collision' => false);
        $rec_arr = explode("_", $rec_type);
        $type = $rec_arr[0];
        $count = (int) $rec_arr[1];
        $day = (int) $rec_arr[2];
        $count2 = (int) $rec_arr[3];
        $days_extra = explode("#", $rec_arr[4]);
        $days = $days_extra[0];
        $extra = (int) $days_extra[1];
        $event_length = (int) $event_length;
        $occurrences = '';

        $tz_from = 'UTC';
        $tz_to = 'America/Los_Angeles';
        $start_dt = new DateTime($start_date, new DateTimeZone($tz_from));
        $start_dt->setTimeZone(new DateTimeZone($tz_to));
        $start_date = $start_dt->format('Y-m-d H:i:s');

        if ($end_date !== '9999-02-01 00:00:00') {
            $end_dt = new DateTime($end_date, new DateTimeZone($tz_from));
            $end_dt->setTimeZone(new DateTimeZone($tz_to));
            $end_date = $end_dt->format('Y-m-d H:i:s');
        }

        foreach ($non_repeat_programs as $program) {
            $program_start_date = $program['start_date'];
            $program_end_date = $program['end_date'];
            syslog(LOG_NOTICE, "SMH DEBUG : start_date: " . print_r($start_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : end_date: " . print_r($end_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : program_start_date: " . print_r($program_start_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : program_end_date: " . print_r($program_end_date, true));

            $tz_from = 'UTC';
            $tz_to = 'America/Los_Angeles';
            $start_dt = new DateTime($program_start_date, new DateTimeZone($tz_from));
            $start_dt->setTimeZone(new DateTimeZone($tz_to));
            $program_start_date = $start_dt->format('Y-m-d H:i:s');

            if ($program_end_date !== '9999-02-01 00:00:00') {
                $end_dt = new DateTime($program_end_date, new DateTimeZone($tz_from));
                $end_dt->setTimeZone(new DateTimeZone($tz_to));
                $program_end_date = $end_dt->format('Y-m-d H:i:s');
            }

            if ($type === 'day') {
                $occurrences = $this->day($start_date, $end_date, $program_start_date, $program_end_date, $count, $event_length, $extra);
            } else if ($type === 'week') {
                $occurrences = $this->week($start_date, $end_date, $program_start_date, $program_end_date, $count, $event_length, $days, $extra);
            } else if ($type === 'month') {
                $occurrences = $this->month($start_date, $end_date, $program_start_date, $program_end_date, $count, $event_length, $day, $count2, $extra);
            } else if ($type === 'year') {
                $occurrences = $this->year($start_date, $end_date, $program_start_date, $program_end_date, $count, $event_length, $day, $count2, $extra);
            }

            syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs1: " . print_r($occurrences, true));

            foreach ($occurrences as $occurrence) {
                $occurrence_start_date = $occurrence['start_date'];
                $occurrence_end_date = $occurrence['end_date'];

                $collision = $this->datesOverlap($occurrence_start_date, $occurrence_end_date, $program_start_date, $program_end_date);
                syslog(LOG_NOTICE, "SMH DEBUG : process_non_rec_programs_a: " . print_r($collision, true));
                if ($collision) {
                    $success = array('collision' => true);
                    break 2;
                }
            }
        }
        return $success;
    }

    public function process_rec_programs_a($start_date, $end_date, $rec_type, $event_length, $repeat_programs) {
        $success = array('collision' => false);

        $rec_arr = explode("_", $rec_type);
        $type = $rec_arr[0];
        $count = (int) $rec_arr[1];
        $day = (int) $rec_arr[2];
        $count2 = (int) $rec_arr[3];
        $days_extra = explode("#", $rec_arr[4]);
        $days = $days_extra[0];
        $extra = (int) $days_extra[1];
        $event_length = (int) $event_length;
        $occurrences = '';

        $tz_from = 'UTC';
        $tz_to = 'America/Los_Angeles';
        $start_dt = new DateTime($start_date, new DateTimeZone($tz_from));
        $start_dt->setTimeZone(new DateTimeZone($tz_to));
        $start_date = $start_dt->format('Y-m-d H:i:s');

        if ($end_date !== '9999-02-01 00:00:00') {
            $end_dt = new DateTime($end_date, new DateTimeZone($tz_from));
            $end_dt->setTimeZone(new DateTimeZone($tz_to));
            $end_date = $end_dt->format('Y-m-d H:i:s');
        }

        syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs_a start_date: " . print_r($start_date, true));
        syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs_a end_date: " . print_r($end_date, true));

        foreach ($repeat_programs as $program) {
            $program_start_date = $program['start_date'];
            $program_end_date = $program['end_date'];
            $program_rec_arr = explode("_", $program['rec_type']);
            $program_type = $program_rec_arr[0];
            $program_count = (int) $program_rec_arr[1];
            $program_day = (int) $program_rec_arr[2];
            $program_count2 = (int) $program_rec_arr[3];
            $program_days_extra = explode("#", $program_rec_arr[4]);
            $program_days = $program_days_extra[0];
            $program_extra = (int) $program_days_extra[1];
            $program_event_length = (int) $program['event_length'];
            $program_occurrences = '';

            $tz_from = 'UTC';
            $tz_to = 'America/Los_Angeles';
            $start_dt = new DateTime($program_start_date, new DateTimeZone($tz_from));
            $start_dt->setTimeZone(new DateTimeZone($tz_to));
            $program_start_date = $start_dt->format('Y-m-d H:i:s');

            if ($program_end_date !== '9999-02-01 00:00:00') {
                $end_dt = new DateTime($program_end_date, new DateTimeZone($tz_from));
                $end_dt->setTimeZone(new DateTimeZone($tz_to));
                $program_end_date = $end_dt->format('Y-m-d H:i:s');
            }

            if ($start_date <= $program_start_date) {
                $program_start_check = $program_start_date;
                $additoinal_dates = $count * 10;
                $program_end_mod = new DateTime($program_start_date . ' +12 month');
                $program_end_check = $program_end_mod->format('Y-m-d H:i:s');
            } else if ($start_date > $program_start_date) {
                $program_start_check = $start_date;
                $additoinal_dates = $count * 10;
                $program_end_mod = new DateTime($start_date . ' +12 month');
                $program_end_check = $program_end_mod->format('Y-m-d H:i:s');
            }

            if ($program_type === 'day') {
                $program_occurrences = $this->day($program_start_date, $program_end_date, $program_start_check, $program_end_check, $program_count, $program_event_length, $program_extra);
            } else if ($program_type === 'week') {
                $program_occurrences = $this->week($program_start_date, $program_end_date, $program_start_check, $program_end_check, $program_count, $program_event_length, $program_days, $program_extra);
            } else if ($program_type === 'month') {
                $program_occurrences = $this->month($program_start_date, $program_end_date, $program_start_check, $program_end_check, $program_count, $program_event_length, $program_day, $program_count2, $program_extra);
            } else if ($program_type === 'year') {
                $program_occurrences = $this->year($program_start_date, $program_end_date, $program_start_check, $program_end_check, $program_count, $program_event_length, $program_day, $program_count2, $program_extra);
            }

            syslog(LOG_NOTICE, "SMH DEBUG : program_occurrences: " . print_r($program_occurrences, true));

            $start_check = reset($program_occurrences);
            $end_check = end($program_occurrences);

            if ($type === 'day') {
                $occurrences = $this->day($start_date, $end_date, $start_check['start_date'], $end_check['end_date'], $count, $event_length, $extra);
            } else if ($type === 'week') {
                $occurrences = $this->week($start_date, $end_date, $start_check['start_date'], $end_check['end_date'], $count, $event_length, $days, $extra);
            } else if ($type === 'month') {
                $occurrences = $this->month($start_date, $end_date, $start_check['start_date'], $end_check['end_date'], $count, $event_length, $day, $count2, $extra);
            } else if ($type === 'year') {
                $occurrences = $this->year($start_date, $end_date, $start_check['start_date'], $end_check['end_date'], $count, $event_length, $day, $count2, $extra);
            }

            syslog(LOG_NOTICE, "SMH DEBUG : occurrences: " . print_r($occurrences, true));

            foreach ($program_occurrences as $program_occurrence) {
                $program_occurrence_start_date = $program_occurrence['start_date'];
                $program_occurrence_end_date = $program_occurrence['end_date'];

                foreach ($occurrences as $occurrence) {
                    $occurrence_start_date = $occurrence['start_date'];
                    $occurrence_end_date = $occurrence['end_date'];
                    $collision = $this->datesOverlap($program_occurrence_start_date, $program_occurrence_end_date, $occurrence_start_date, $occurrence_end_date);
                    syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs: collision: " . print_r($collision, true));
                    if ($collision) {
                        $success = array('collision' => true);
                        break 3;
                    }
                }
            }
        }
        return $success;
    }

    public function process_rec_programs_b($start_date, $end_date, $repeat_programs) {
        $success = array('collision' => false);
        $tz_from = 'UTC';
        $tz_to = 'America/Los_Angeles';
        $start_dt = new DateTime($start_date, new DateTimeZone($tz_from));
        $start_dt->setTimeZone(new DateTimeZone($tz_to));
        $start_date = $start_dt->format('Y-m-d H:i:s');

        if ($end_date !== '9999-02-01 00:00:00') {
            $end_dt = new DateTime($end_date, new DateTimeZone($tz_from));
            $end_dt->setTimeZone(new DateTimeZone($tz_to));
            $end_date = $end_dt->format('Y-m-d H:i:s');
        }
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

            $tz_from = 'UTC';
            $tz_to = 'America/Los_Angeles';
            $start_dt = new DateTime($program_start_date, new DateTimeZone($tz_from));
            $start_dt->setTimeZone(new DateTimeZone($tz_to));
            $program_start_date = $start_dt->format('Y-m-d H:i:s');

            if ($program_end_date !== '9999-02-01 00:00:00') {
                $end_dt = new DateTime($program_end_date, new DateTimeZone($tz_from));
                $end_dt->setTimeZone(new DateTimeZone($tz_to));
                $program_end_date = $end_dt->format('Y-m-d H:i:s');
            }

            syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs_a start_date: " . print_r($start_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs_a end_date: " . print_r($end_date, true));

            syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs: start_date: " . print_r($start_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs: end_date: " . print_r($end_date, true));

            if ($type === 'day') {
                $occurrences = $this->day($program_start_date, $program_end_date, $start_date, $end_date, $count, $event_length, $extra);
            } else if ($type === 'week') {
                $occurrences = $this->week($program_start_date, $program_end_date, $start_date, $end_date, $count, $event_length, $days, $extra);
            } else if ($type === 'month') {
                $occurrences = $this->month($program_start_date, $program_end_date, $start_date, $end_date, $count, $event_length, $day, $count2, $extra);
            } else if ($type === 'year') {
                $occurrences = $this->year($program_start_date, $program_end_date, $start_date, $end_date, $count, $event_length, $day, $count2, $extra);
            }
            syslog(LOG_NOTICE, "SMH DEBUG : type: " . print_r($type, true));
            syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs1: " . print_r($occurrences, true));

            foreach ($occurrences as $occurrence) {
                $occurrence_start_date = $occurrence['start_date'];
                $occurrence_end_date = $occurrence['end_date'];
                $collision = $this->datesOverlap($start_date, $end_date, $occurrence_start_date, $occurrence_end_date);
                syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs2: " . print_r($collision, true));
                if ($collision) {
                    $success = array('collision' => true);
                    break 2;
                }
            }
        }
        return $success;
    }

    public function process_non_rec_programs_b($start_date, $end_date, $non_repeat_programs) {
        $success = array('collision' => false);
        $tz_from = 'UTC';
        $tz_to = 'America/Los_Angeles';
        $start_dt = new DateTime($start_date, new DateTimeZone($tz_from));
        $start_dt->setTimeZone(new DateTimeZone($tz_to));
        $start_date = $start_dt->format('Y-m-d H:i:s');

        if ($end_date !== '9999-02-01 00:00:00') {
            $end_dt = new DateTime($end_date, new DateTimeZone($tz_from));
            $end_dt->setTimeZone(new DateTimeZone($tz_to));
            $end_date = $end_dt->format('Y-m-d H:i:s');
        }

        foreach ($non_repeat_programs as $program) {
            $program_start_date = $program['start_date'];
            $program_end_date = $program['end_date'];

            $tz_from = 'UTC';
            $tz_to = 'America/Los_Angeles';
            $start_dt = new DateTime($program_start_date, new DateTimeZone($tz_from));
            $start_dt->setTimeZone(new DateTimeZone($tz_to));
            $program_start_date = $start_dt->format('Y-m-d H:i:s');

            if ($program_end_date !== '9999-02-01 00:00:00') {
                $end_dt = new DateTime($program_end_date, new DateTimeZone($tz_from));
                $end_dt->setTimeZone(new DateTimeZone($tz_to));
                $program_end_date = $end_dt->format('Y-m-d H:i:s');
            }

            $collision = $this->datesOverlap($start_date, $end_date, $program_start_date, $program_end_date);
            syslog(LOG_NOTICE, "SMH DEBUG : process_non_rec_programs: " . print_r($collision, true));
            if ($collision) {
                $success = array('collision' => true);
                break;
            }
        }
        return $success;
    }

    public function process_rec_programs_build_schedule($start_date, $end_date, $program_start_check, $program_end_check, $rec_type, $event_length) {
        $success = array('success' => false);

        $rec_arr = explode("_", $rec_type);
        $type = $rec_arr[0];
        $count = (int) $rec_arr[1];
        $day = (int) $rec_arr[2];
        $count2 = (int) $rec_arr[3];
        $days_extra = explode("#", $rec_arr[4]);
        $days = $days_extra[0];
        $extra = (int) $days_extra[1];
        $event_length = (int) $event_length;
        $occurrences = '';

        $start_dt = new DateTime($start_date);
        $start_date = $start_dt->format('Y-m-d H:i:s');

        if ($end_date !== '9999-02-01 00:00:00') {
            $end_dt = new DateTime($end_date);
            $end_date = $end_dt->format('Y-m-d H:i:s');
        }

        syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs_build_schedule start_date: " . print_r($start_date, true));
        syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs_build_schedule end_date: " . print_r($end_date, true));
        syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs_build_schedule program_start_check: " . print_r($program_start_check, true));
        syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs_build_schedule program_end_check: " . print_r($program_end_check, true));

        if ($type === 'day') {
            $occurrences = $this->day($start_date, $end_date, $program_start_check, $program_end_check, $count, $event_length, $extra);
        } else if ($type === 'week') {
            $occurrences = $this->week($start_date, $end_date, $program_start_check, $program_end_check, $count, $event_length, $days, $extra);
        } else if ($type === 'month') {
            $occurrences = $this->month($start_date, $end_date, $program_start_check, $program_end_check, $count, $event_length, $day, $count2, $extra);
        } else if ($type === 'year') {
            $occurrences = $this->year($start_date, $end_date, $program_start_check, $program_end_check, $count, $event_length, $day, $count2, $extra);
        }

        $date_range_found = array();
        foreach ($occurrences as $occurrence) {
            $occurrence_start_date = $occurrence['start_date'];
            $occurrence_end_date = $occurrence['end_date'];
            $collision = $this->datesOverlap($program_start_check, $program_end_check, $occurrence_start_date, $occurrence_end_date);
            syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs_build_schedule: collision:" . print_r($collision, true));
            if ($collision) {
                $date_range_found['start_date'] = $occurrence_start_date;
                $date_range_found['end_date'] = $occurrence_end_date;
//                $success = array('collision' => true);
//                break 2;
            }
        }

        $success = array('success' => true, 'date_range_found' => $date_range_found);

        syslog(LOG_NOTICE, "SMH DEBUG : process_rec_programs_build_schedule occurrences: " . print_r($occurrences, true));

        return $success;
    }

    public function day($program_start_date, $program_end_date, $start_date, $end_date, $count, $event_length, $extra) {
        $r = new When();
        $start_date_mod = new DateTime($start_date . ' -1 day');
        //$start_date_mod->modify('first day of this month');
        $new_start_date = $start_date_mod->format('Y-m-d 00:00:00');

        $end_date_mod = new DateTime($end_date . ' +1 day');
        //$end_date_mod->modify('last day of this month');
        $new_end_date = $end_date_mod->format('Y-m-d 00:00:00');

        if ($program_end_date === '9999-02-01 00:00:00') {
            syslog(LOG_NOTICE, "SMH DEBUG : day: program_start_date: " . print_r($program_start_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : day: start_date_between: " . print_r($new_start_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : day: end_date_between: " . print_r($new_end_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : day: count: " . print_r($count, true));
            syslog(LOG_NOTICE, "SMH DEBUG : day: end_date: " . print_r($end_date, true));

            $r->startDate(new DateTime($program_start_date))
                    ->freq("daily")
                    ->interval($count);
            $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
        } else {
            if ($extra) {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("daily")
                        ->interval($count)
                        ->count($extra)
                        ->until(new DateTime($program_end_date));
                $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
            } else {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("daily")
                        ->interval($count)
                        ->until(new DateTime($program_end_date));
                $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
            }
        }

        $programs = array();
        foreach ($occurrences as $occurr) {
            $start_date = $occurr->format('Y-m-d H:i:s');

            $dateinsec = strtotime($start_date);
            $newdate = $dateinsec + $event_length;
            $end_date = date('Y-m-d H:i:s', $newdate);
            array_push($programs, array('start_date' => $start_date, 'end_date' => $end_date));
        }

        return $programs;
    }

    public function week($program_start_date, $program_end_date, $start_date, $end_date, $count, $event_length, $days, $extra) {
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

        $start_date_mod = new DateTime($start_date . ' -1 day');
        //$start_date_mod->modify('first day of this month');
        $new_start_date = $start_date_mod->format('Y-m-d 00:00:00');

        $end_date_mod = new DateTime($end_date . ' +1 day');
        //$end_date_mod->modify('last day of this month');
        $new_end_date = $end_date_mod->format('Y-m-d 00:00:00');

//        $tz_from = 'UTC';
//        $tz_to = 'America/Los_Angeles';
//        $start_dt = new DateTime($program_start_date, new DateTimeZone($tz_from));
//        $start_dt->setTimeZone(new DateTimeZone($tz_to));
//        $program_start_date = $start_dt->format('Y-m-d H:i:s');

        $r = new When();
        if ($program_end_date === '9999-02-01 00:00:00') {
            syslog(LOG_NOTICE, "SMH DEBUG : week: program_start_date: " . print_r($program_start_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : week: start_date_between: " . print_r($new_start_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : week: end_date_between: " . print_r($new_end_date, true));
            syslog(LOG_NOTICE, "SMH DEBUG : week: count: " . print_r($count, true));
            syslog(LOG_NOTICE, "SMH DEBUG : week: days_arr: " . print_r($days_arr, true));

            $r->startDate(new DateTime($program_start_date))
                    ->freq("weekly")
                    ->interval($count)
                    ->byday($days_arr);
            $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
        } else {
            if ($extra) {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("weekly")
                        ->interval($count)
                        ->count($extra)
                        ->byday($days_arr)
                        ->until(new DateTime($program_end_date));
                $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
            } else {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("weekly")
                        ->interval($count)
                        ->byday($days_arr)
                        ->until(new DateTime($program_end_date));
                $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
            }
        }

        $programs = array();
        foreach ($occurrences as $occurr) {
            $start_date = $occurr->format('Y-m-d H:i:s');

            $dateinsec = strtotime($start_date);
            $newdate = $dateinsec + $event_length;
            $end_date = date('Y-m-d H:i:s', $newdate);
            array_push($programs, array('start_date' => $start_date, 'end_date' => $end_date));
        }

        return $programs;
    }

    public function month($program_start_date, $program_end_date, $start_date, $end_date, $count, $event_length, $day, $count2, $extra) {
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

        $start_date_mod = new DateTime($start_date . ' -1 day');
        //$start_date_mod->modify('first day of this month');
        $new_start_date = $start_date_mod->format('Y-m-d 00:00:00');

        $end_date_mod = new DateTime($end_date . ' +1 day');
        //$end_date_mod->modify('last day of this month');
        $new_end_date = $end_date_mod->format('Y-m-d 00:00:00');

        syslog(LOG_NOTICE, "SMH DEBUG : month: program_start_date: " . print_r($program_start_date, true));
        syslog(LOG_NOTICE, "SMH DEBUG : month: start_date_between: " . print_r($new_start_date, true));
        syslog(LOG_NOTICE, "SMH DEBUG : month: end_date_between: " . print_r($new_end_date, true));
        syslog(LOG_NOTICE, "SMH DEBUG : month: count: " . print_r($count, true));
        syslog(LOG_NOTICE, "SMH DEBUG : month: end_date: " . print_r($end_date, true));

        if ($program_end_date === '9999-02-01 00:00:00') {
            if ($day) {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("monthly")
                        ->byday($on)
                        ->interval($count);
                $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
            } else {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("monthly")
                        ->interval($count);
                $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
            }
        } else {
            if ($extra) {
                if ($day) {
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("monthly")
                            ->byday($on)
                            ->interval($count)
                            ->count($extra)
                            ->until(new DateTime($program_end_date));
                    $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
                } else {
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("monthly")
                            ->interval($count)
                            ->count($extra)
                            ->until(new DateTime($program_end_date));
                    $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
                }
            } else {
                if ($day) {
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("monthly")
                            ->byday($on)
                            ->interval($count)
                            ->until(new DateTime($program_end_date));
                    $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
                } else {
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("monthly")
                            ->interval($count)
                            ->until(new DateTime($program_end_date));
                    $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
                }
            }
        }

        $programs = array();
        foreach ($occurrences as $occurr) {
            $start_date = $occurr->format('Y-m-d H:i:s');

            $dateinsec = strtotime($start_date);
            $newdate = $dateinsec + $event_length;
            $end_date = date('Y-m-d H:i:s', $newdate);
            array_push($programs, array('start_date' => $start_date, 'end_date' => $end_date));
        }

        return $programs;
    }

    public function year($program_start_date, $program_end_date, $start_date, $end_date, $count, $event_length, $day, $count2, $extra) {
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

        $start_date_mod = new DateTime($start_date . ' -1 day');
        //$start_date_mod->modify('first day of this month');
        $new_start_date = $start_date_mod->format('Y-m-d 00:00:00');
        syslog(LOG_NOTICE, "SMH DEBUG : day: new_start_date: " . print_r($new_start_date, true));

        $end_date_mod = new DateTime($end_date . ' +1 day');
        //$end_date_mod->modify('last day of this month');
        $new_end_date = $end_date_mod->format('Y-m-d 00:00:00');
        syslog(LOG_NOTICE, "SMH DEBUG : day: new_end_date: " . print_r($new_end_date, true));

        syslog(LOG_NOTICE, "SMH DEBUG : year: " . print_r($program_end_date, true));
        if ($program_end_date === '9999-02-01 00:00:00') {
            if ($day) {
                $start_dt = new DateTime($program_start_date);
                $month = (int) $start_dt->format('m');
                syslog(LOG_NOTICE, "SMH DEBUG : year: day: " . print_r($on, true));
                syslog(LOG_NOTICE, "SMH DEBUG : year: count: " . print_r($count, true));
                syslog(LOG_NOTICE, "SMH DEBUG : year: end_date: " . print_r($end_date, true));
                $r->startDate(new DateTime($program_start_date))
                        ->freq("yearly")
                        ->byday($on)
                        ->bymonth($month)
                        ->interval($count);
                $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
            } else {
                $r->startDate(new DateTime($program_start_date))
                        ->freq("yearly")
                        ->interval($count);
                $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
            }
        } else {
            if ($extra) {
                if ($day) {
                    $start_dt = new DateTime($program_start_date);
                    $month = (int) $start_dt->format('m');
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("yearly")
                            ->byday($on)
                            ->bymonth($month)
                            ->interval($count)
                            ->count($extra)
                            ->until(new DateTime($program_end_date));
                    $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
                } else {
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("yearly")
                            ->interval($count)
                            ->count($extra)
                            ->until(new DateTime($program_end_date));
                    $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
                }
            } else {
                if ($day) {
                    $start_dt = new DateTime($program_start_date);
                    $month = (int) $start_dt->format('m');
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("yearly")
                            ->byday($on)
                            ->bymonth($month)
                            ->interval($count)
                            ->until(new DateTime($program_end_date));
                    $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
                } else {
                    $r->startDate(new DateTime($program_start_date))
                            ->freq("yearly")
                            ->interval($count)
                            ->until(new DateTime($program_end_date));
                    $occurrences = $r->getOccurrencesBetween(new DateTime($new_start_date), new DateTime($new_end_date));
                }
            }
        }

        $programs = array();
        foreach ($occurrences as $occurr) {
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