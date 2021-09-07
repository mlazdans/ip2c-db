<?php

class Range {
  var $start;
  var $end;
  var $interval;
  var $merges = 0;
  var $deleted = false;

  function __construct($start, $end) {
    # NOTE: 64-bit system needed for (int) to work correctly, can leave as string too
    $this->start = (int)$start;
    $this->end = (int)$end;
    $this->interval = $this->end - $this->start;
  }

  function __toString() {
      return "$this->start,$this->end";
  }

  function delete(){
    $this->deleted = true;
  }

  function isDeleted(){
    return $this->deleted;
  }

  function isWithin(Range $r) {
    //return (($this->start <= $r->start) && ($this->end >= $r->end)) || (($r->start <= $this->start) && ($r->end >= $this->end));
    return ($this->start >= $r->start) && ($this->end <= $r->end);
  }

  function isEqual(Range $r){
    return ($this->start == $r->start) && ($this->end == $r->end);
  }

  function doOverlap(Range $r) {
    return ($this->start <= $r->end) && ($r->start <= $this->end);
  }

  function doOverlapOrConnect(Range $r) {
    return ($this->start <= $r->end + 1) && ($r->start <= $this->end + 1);
  }

  function doConnect(Range $r) {
    return ($this->start == ($r->end + 1)) || ($r->start == ($this->end + 1));
  }

  function intersection(Range $r){
    $nr = clone $r;
    $nr->start = max($this->start, $r->start);
    $nr->end = min($this->end, $r->end);
    $nr->interval = $nr->end - $nr->start;
    return $nr;
  }

  function substract(Range $r){
    if($this->start < $r->start){
      $this->end = $r->start - 1;
    } elseif($this->start > $r->start){
      $this->start = $r->end + 1;
    }
  }

  function union(Range $r) {
    $this->start = min($this->start, $r->start);
    $this->end = max($this->end, $r->end);
    $this->interval = $this->end - $this->start;
  }

  static function __deleted(Range ...$args){
    $deleted = false;
    foreach($args as $arg){
      if($deleted = $deleted || $arg->deleted){
        break;
      }
    }
    return $deleted;
  }

  static function cmpStartEnd(Range $r1, Range $r2) {
    # TODO: to all compares!!!
    if($r1->deleted || $r2->deleted)return 0;
    if ($r1->start == $r2->end) {
        return 0;
    }
    return ($r1->start < $r2->end) ? 1 : -1;
  }
/*
  static function compareStartEndDesc(Range $r1, Range $r2) {
    if ($r1->start == $r2->end) {
        return 0;
    }
    return ($r1->start < $r2->end) ? -1 : 1;
  }
*/
  static function cmpEndStart(Range $r1, Range $r2) {
    if($r1->deleted || $r2->deleted)return 0;
    if ($r1->end == $r2->start) {
        return 0;
    }
    return ($r1->end > $r2->start) ? 1 : -1;
  }

  static function cmpStart(Range $r1, Range $r2) {
    if($r1->deleted || $r2->deleted)return 0;
    if ($r1->start == $r2->start) {
        return Range::cmpEnd($r1, $r2);
    }
    return ($r1->start > $r2->start) ? 1 : -1;
  }

  static function cmpStartDesc(Range $r1, Range $r2) {
    if($r1->deleted || $r2->deleted)return 0;
    return Range::cmpStart($r1, $r2) * (-1);
  }

  static function cmpEndAsc(Range $r1, Range $r2) {
    if($r1->deleted || $r2->deleted)return 0;
    return Range::cmpEnd($r1, $r2) * (-1);
  }

  static function cmpInterval(Range $r1, Range $r2) {
    if($r1->deleted || $r2->deleted)return 0;
    if ($r1->interval == $r2->interval) {
        return Range::cmpStart($r1, $r2);
    }
    return ($r1->interval < $r2->interval) ? 1 : -1;
  }

  static function cmpStartInterval(Range $r1, Range $r2) {
    if($r1->deleted || $r2->deleted)return 0;
    if ($r1->start == $r2->start) {
        return Range::cmpInterval($r1, $r2);
    }
    return Range::cmpStart($r1, $r2);
  }

  static function cmpEnd(Range $r1, Range $r2) {
    if($r1->deleted || $r2->deleted)return 0;
    if ($r1->end == $r2->end) {
        return 0;
    }
    return ($r1->end > $r2->end) ? 1 : -1;
  }
  static function cmpEndDesc(Range $r1, Range $r2) {
    if($r1->deleted || $r2->deleted)return 0;
    return Range::cmpEnd($r1, $r2) * (-1);
  }
}
