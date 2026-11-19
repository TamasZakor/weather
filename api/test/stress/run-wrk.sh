#!/bin/sh

# WRK Terheléstesztelő script a meteorológiai API-hoz
# Használat: ./run-wrk.sh [BASE_URL]
BASE_URL=${1:-"http://localhost:8080"}

echo "=========================================================="
echo "  Meteorológiai API WRK Benchmark Futtatása"
echo "  Cél URL: $BASE_URL"
echo "=========================================================="
echo ""

echo "[1/3] /api/meta végpont mérése (2 szál, 50 kapcsolat, 10 mp)..."
wrk -t2 -c50 -d10s --latency "$BASE_URL/api/meta"
echo ""

echo "[2/3] /api/weather/city végpont mérése (1 év Budapest)..."
wrk -t2 -c30 -d10s --latency "$BASE_URL/api/weather/city?cityId=1&startDate=1950-01-01&endDate=1950-12-31"
echo ""

echo "[3/3] /api/weather/region végpont mérése (Dunántúl régió)..."
wrk -t2 -c30 -d10s --latency "$BASE_URL/api/weather/region?region=Dun%C3%A1nt%C3%BAl&startDate=1950-01-01&endDate=1950-12-31"
echo ""

echo "=========================================================="
echo "  Mérés befejeződött!"
echo "=========================================================="
