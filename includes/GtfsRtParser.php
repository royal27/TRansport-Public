<?php
/**
 * Lightweight pure-PHP GTFS-RT Protobuf parser.
 * Designed to decode VehiclePositions specifically from TPBI GTFS-RT feed.
 */

class GtfsRtParser {

    private static function readVarint(&$data, &$offset) {
        $result = 0;
        $shift = 0;
        while ($offset < strlen($data)) {
            $byte = ord($data[$offset++]);
            $result |= ($byte & 0x7F) << $shift;
            if (($byte & 0x80) == 0) {
                return $result;
            }
            $shift += 7;
        }
        return $result;
    }

    private static function readFloat(&$data, &$offset) {
        if ($offset + 4 > strlen($data)) return 0.0;
        $bytes = substr($data, $offset, 4);
        $offset += 4;
        return unpack('f', $bytes)[1];
    }

    private static function readString(&$data, &$offset, $length) {
        if ($offset + $length > strlen($data)) return "";
        $str = substr($data, $offset, $length);
        $offset += $length;
        return $str;
    }

    public static function parseVehiclePositions($data) {
        $offset = 0;
        $vehicles = [];
        $dataLen = strlen($data);

        while($offset < $dataLen) {
            $key = self::readVarint($data, $offset);
            $field = $key >> 3;
            $wire = $key & 0x07;

            if ($wire == 0) { self::readVarint($data, $offset); }
            elseif ($wire == 5) { $offset += 4; }
            elseif ($wire == 1) { $offset += 8; }
            elseif ($wire == 2) {
                $len = self::readVarint($data, $offset);

                if ($field == 2) { // Entity
                    $entityEnd = $offset + $len;

                    $id = '';
                    $routeId = '';
                    $lat = 0;
                    $lon = 0;
                    $bearing = 0;
                    $speed = 0;
                    $plate = '';

                    while($offset < $entityEnd) {
                        $eKey = self::readVarint($data, $offset);
                        $eField = $eKey >> 3;
                        $eWire = $eKey & 0x07;

                        if ($eWire == 0) { self::readVarint($data, $offset); }
                        elseif ($eWire == 5) { $offset += 4; }
                        elseif ($eWire == 1) { $offset += 8; }
                        elseif ($eWire == 2) {
                            $eLen = self::readVarint($data, $offset);
                            if ($eField == 4) { // VehiclePosition
                                $vpEnd = $offset + $eLen;
                                while($offset < $vpEnd) {
                                    $vKey = self::readVarint($data, $offset);
                                    $vField = $vKey >> 3;
                                    $vWire = $vKey & 0x07;

                                    if ($vWire == 0) { self::readVarint($data, $offset); }
                                    elseif ($vWire == 5) { $offset += 4; }
                                    elseif ($vWire == 1) { $offset += 8; }
                                    elseif ($vWire == 2) {
                                        $vLen = self::readVarint($data, $offset);

                                        if ($vField == 1) { // TripDescriptor
                                            $tdEnd = $offset + $vLen;
                                            while($offset < $tdEnd) {
                                                $tKey = self::readVarint($data, $offset);
                                                $tField = $tKey >> 3;
                                                $tWire = $tKey & 0x07;
                                                if ($tWire == 2) {
                                                    $tLen = self::readVarint($data, $offset);
                                                    if ($tField == 5) $routeId = self::readString($data, $offset, $tLen);
                                                    else $offset += $tLen;
                                                } elseif ($tWire == 0) self::readVarint($data, $offset);
                                                elseif ($tWire == 5) $offset += 4;
                                                elseif ($tWire == 1) $offset += 8;
                                            }
                                        } elseif ($vField == 2) { // Position
                                            $pEnd = $offset + $vLen;
                                            while($offset < $pEnd) {
                                                $pKey = self::readVarint($data, $offset);
                                                $pField = $pKey >> 3;
                                                $pWire = $pKey & 0x07;
                                                if ($pWire == 5) {
                                                    if ($pField == 1) $lat = self::readFloat($data, $offset);
                                                    elseif ($pField == 2) $lon = self::readFloat($data, $offset);
                                                    elseif ($pField == 3) $bearing = self::readFloat($data, $offset);
                                                    elseif ($pField == 5) $speed = self::readFloat($data, $offset);
                                                    else $offset += 4;
                                                } elseif ($pWire == 0) self::readVarint($data, $offset);
                                                elseif ($pWire == 1) $offset += 8;
                                                elseif ($pWire == 2) {
                                                    $pLen = self::readVarint($data, $offset);
                                                    $offset += $pLen;
                                                }
                                            }
                                        } elseif ($vField == 8) { // VehicleDescriptor
                                            $vdEnd = $offset + $vLen;
                                            while($offset < $vdEnd) {
                                                $vdKey = self::readVarint($data, $offset);
                                                $vdField = $vdKey >> 3;
                                                $vdWire = $vdKey & 0x07;
                                                if ($vdWire == 2) {
                                                    $vdLen = self::readVarint($data, $offset);
                                                    if ($vdField == 1) $id = self::readString($data, $offset, $vdLen);
                                                    elseif ($vdField == 3) $plate = self::readString($data, $offset, $vdLen);
                                                    else $offset += $vdLen;
                                                } elseif ($vdWire == 0) self::readVarint($data, $offset);
                                                elseif ($vdWire == 5) $offset += 4;
                                                elseif ($vdWire == 1) $offset += 8;
                                            }
                                        }
                                        else {
                                            $offset += $vLen;
                                        }
                                    }
                                }
                            } else {
                                $offset += $eLen;
                            }
                        }
                    }
                    if ($lat != 0) {
                        $vehicles[] = [
                            'id' => $id,
                            'routeId' => $routeId,
                            'lat' => $lat,
                            'lon' => $lon,
                            'bearing' => $bearing,
                            'speed' => $speed * 3.6, // m/s to km/h
                            'plate' => $plate
                        ];
                    }
                } else {
                    $offset += $len;
                }
            }
        }
        return $vehicles;
    }
}
