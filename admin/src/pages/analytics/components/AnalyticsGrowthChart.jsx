import { useState } from 'react';
import { TrendingUp, Calendar } from 'lucide-react';

/**
 * Format date string (YYYY-MM-DD) into readable short format: "Sep 06"
 */
function formatShortDate(dateStr) {
  if (!dateStr) return '';
  const parts = String(dateStr).split('-');
  if (parts.length === 3) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const mIdx = parseInt(parts[1], 10) - 1;
    const d = parseInt(parts[2], 10);
    return `${months[mIdx] || parts[1]} ${String(d).padStart(2, '0')}`;
  }
  return dateStr;
}

/**
 * Format date string (YYYY-MM-DD) into full readable format: "Sep 06, 2026"
 */
function formatFullDate(dateStr) {
  if (!dateStr) return '';
  const parts = String(dateStr).split('-');
  if (parts.length === 3) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const mIdx = parseInt(parts[1], 10) - 1;
    const d = parseInt(parts[2], 10);
    return `${months[mIdx] || parts[1]} ${String(d).padStart(2, '0')}, ${parts[0]}`;
  }
  return dateStr;
}

/**
 * Generate nice integer ticks for the Y-axis so no fractional numbers appear.
 */
function getNiceYAxis(maxCount) {
  if (maxCount <= 0) {
    return { maxVal: 4, ticks: [0, 1, 2, 3, 4] };
  }
  if (maxCount <= 4) {
    return { maxVal: 4, ticks: [0, 1, 2, 3, 4] };
  }
  if (maxCount <= 8) {
    return { maxVal: 8, ticks: [0, 2, 4, 6, 8] };
  }
  if (maxCount <= 12) {
    return { maxVal: 12, ticks: [0, 3, 6, 9, 12] };
  }
  if (maxCount <= 20) {
    return { maxVal: 20, ticks: [0, 5, 10, 15, 20] };
  }

  // Calculate dynamic nice step
  const roughStep = maxCount / 4;
  const power = Math.floor(Math.log10(roughStep));
  const magnitude = Math.pow(10, power);
  const normalized = roughStep / magnitude;

  let step;
  if (normalized <= 1) step = 1 * magnitude;
  else if (normalized <= 2) step = 2 * magnitude;
  else if (normalized <= 5) step = 5 * magnitude;
  else step = 10 * magnitude;

  step = Math.max(1, Math.round(step));
  const maxVal = step * 4;
  const ticks = [0, step, step * 2, step * 3, maxVal];

  return { maxVal, ticks };
}

export function AnalyticsGrowthChart({
  dates = [],
  counts = [],
  loading = false,
}) {
  const [hoveredIndex, setHoveredIndex] = useState(null);

  if (loading) {
    return (
      <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-4">
        <div className="flex items-center justify-between">
          <div className="h-4 w-44 bg-slate-200 animate-pulse rounded" />
          <div className="h-4 w-24 bg-slate-200 animate-pulse rounded" />
        </div>
        <div className="h-56 w-full bg-slate-100 animate-pulse rounded-xl" />
      </div>
    );
  }

  const pointsCount = dates.length;
  const numericCounts = counts.map((c) => Number(c) || 0);
  const totalNew = numericCounts.reduce((acc, val) => acc + val, 0);
  const maxCount = numericCounts.length > 0 ? Math.max(...numericCounts) : 0;
  const { maxVal, ticks } = getNiceYAxis(maxCount);

  // SVG Dimension Constants
  const width = 800;
  const height = 240;
  const paddingLeft = 45;
  const paddingRight = 30;
  const paddingTop = 25;
  const paddingBottom = 45;
  const graphWidth = width - paddingLeft - paddingRight;
  const graphHeight = height - paddingTop - paddingBottom;

  // Coordinate Helpers
  const getX = (index) => {
    if (pointsCount <= 1) {
      return paddingLeft + graphWidth / 2;
    }
    return paddingLeft + (index / (pointsCount - 1)) * graphWidth;
  };

  const getY = (val) => {
    return height - paddingBottom - (val / maxVal) * graphHeight;
  };

  // Build SVG Paths
  let linePath = '';
  let areaPath = '';

  if (pointsCount > 1) {
    linePath = numericCounts.reduce((acc, val, i) => {
      const x = getX(i);
      const y = getY(val);
      return i === 0 ? `M ${x},${y}` : `${acc} L ${x},${y}`;
    }, '');

    areaPath = `${linePath} L ${getX(pointsCount - 1)},${height - paddingBottom} L ${getX(0)},${height - paddingBottom} Z`;
  }

  // Label sampling for X-Axis to prevent collision on larger ranges
  const shouldShowLabel = (idx) => {
    if (pointsCount <= 1) return true;
    if (pointsCount <= 10) return true;
    if (pointsCount <= 16) return idx % 2 === 0 || idx === pointsCount - 1;
    if (pointsCount <= 31) return idx % 5 === 0 || idx === pointsCount - 1;
    return idx % Math.ceil(pointsCount / 6) === 0 || idx === pointsCount - 1;
  };

  const isSingleDay = pointsCount === 1;

  return (
    <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-2">
        <div className="flex items-center space-x-2">
          <TrendingUp className="w-4 h-4 text-blue-600" />
          <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
            Member Registration Trend
          </h4>
        </div>
        <div className="flex items-center space-x-3">
          <span className="text-xs font-semibold text-slate-500">
            Total New: <strong className="text-slate-900 font-extrabold text-sm">{totalNew.toLocaleString()}</strong>
          </span>
        </div>
      </div>

      {pointsCount === 0 ? (
        <div className="h-56 flex flex-col items-center justify-center text-xs text-slate-400 bg-slate-50 rounded-xl space-y-1">
          <Calendar className="w-5 h-5 text-slate-300 mb-1" />
          <span>No registration activity recorded for the selected period.</span>
        </div>
      ) : (
        <div className="relative">
          {/* SVG Line Chart */}
          <div className="w-full">
            <svg
              viewBox={`0 0 ${width} ${height}`}
              className="w-full h-56 sm:h-64 overflow-visible select-none"
              onMouseLeave={() => setHoveredIndex(null)}
            >
              <defs>
                <linearGradient id="regChartGradient" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stopColor="#2563eb" stopOpacity="0.30" />
                  <stop offset="100%" stopColor="#2563eb" stopOpacity="0.0" />
                </linearGradient>
              </defs>

              {/* Y-Axis Grid Lines & Integer Labels */}
              {ticks.map((tickVal, i) => {
                const y = getY(tickVal);
                return (
                  <g key={`tick-${i}`}>
                    <line
                      x1={paddingLeft}
                      y1={y}
                      x2={width - paddingRight}
                      y2={y}
                      stroke="#f1f5f9"
                      strokeDasharray="4 4"
                      strokeWidth="1"
                    />
                    <text
                      x={paddingLeft - 8}
                      y={y + 3.5}
                      textAnchor="end"
                      className="text-[11px] fill-slate-400 font-sans font-medium"
                    >
                      {tickVal}
                    </text>
                  </g>
                );
              })}

              {/* Zero Baseline Solid Line */}
              <line
                x1={paddingLeft}
                y1={getY(0)}
                x2={width - paddingRight}
                y2={getY(0)}
                stroke="#e2e8f0"
                strokeWidth="1.5"
              />

              {/* Multi-Day: Gradient Area Fill */}
              {pointsCount > 1 && (
                <path d={areaPath} fill="url(#regChartGradient)" />
              )}

              {/* Multi-Day: Continuous Line Path */}
              {pointsCount > 1 && (
                <path
                  d={linePath}
                  fill="none"
                  stroke="#2563eb"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                />
              )}

              {/* Single-Day Guide Line & Baseline Highlight */}
              {isSingleDay && (
                <g>
                  {/* Subtle vertical drop line from point to baseline */}
                  <line
                    x1={getX(0)}
                    y1={getY(numericCounts[0])}
                    x2={getX(0)}
                    y2={getY(0)}
                    stroke="#93c5fd"
                    strokeWidth="2"
                    strokeDasharray="4 4"
                  />
                </g>
              )}

              {/* Interactive Points, Hover Columns, and X-Axis Labels */}
              {dates.map((dateStr, idx) => {
                const x = getX(idx);
                const y = getY(numericCounts[idx]);
                const isHovered = hoveredIndex === idx;
                const columnWidth = isSingleDay
                  ? graphWidth
                  : graphWidth / Math.max(pointsCount - 1, 1);

                return (
                  <g key={`pt-${idx}`}>
                    {/* Invisible Hover Trigger Rect */}
                    <rect
                      x={isSingleDay ? paddingLeft : x - columnWidth / 2}
                      y={paddingTop}
                      width={columnWidth}
                      height={graphHeight}
                      fill="transparent"
                      className="cursor-pointer"
                      onMouseEnter={() => setHoveredIndex(idx)}
                    />

                    {/* Active Hover Vertical Crosshair */}
                    {isHovered && (
                      <line
                        x1={x}
                        y1={paddingTop}
                        x2={x}
                        y2={height - paddingBottom}
                        stroke="#94a3b8"
                        strokeDasharray="3 3"
                        strokeWidth="1.5"
                      />
                    )}

                    {/* Visible Data Point Dot */}
                    <circle
                      cx={x}
                      cy={y}
                      r={isHovered ? 6 : isSingleDay ? 5.5 : (pointsCount <= 14 ? 4 : 3)}
                      fill="#ffffff"
                      stroke="#2563eb"
                      strokeWidth={isHovered ? 2.5 : 2}
                      className="transition-all duration-150 pointer-events-none"
                    />

                    {/* Highlighted Value Ring for Single Day */}
                    {isSingleDay && (
                      <circle
                        cx={x}
                        cy={y}
                        r={10}
                        fill="none"
                        stroke="#bfdbfe"
                        strokeWidth="2"
                        className="animate-pulse pointer-events-none"
                      />
                    )}

                    {/* X-Axis Readable Date Label */}
                    {shouldShowLabel(idx) && (
                      <text
                        x={x}
                        y={height - 14}
                        textAnchor="middle"
                        className={`text-[11px] font-sans transition-colors ${
                          isHovered
                            ? 'fill-blue-600 font-bold'
                            : 'fill-slate-500 font-medium'
                        }`}
                      >
                        {formatShortDate(dateStr)}
                      </text>
                    )}
                  </g>
                );
              })}
            </svg>
          </div>

          {/* Floating Hover Tooltip */}
          {hoveredIndex !== null && dates[hoveredIndex] && (
            <div
              className="absolute top-2 pointer-events-none bg-slate-900 text-white rounded-xl px-3 py-2 text-xs shadow-xl border border-slate-700/80 z-20 flex flex-col space-y-1 transition-all duration-150"
              style={{
                left: isSingleDay
                  ? '50%'
                  : `${(paddingLeft / width) * 100 + (hoveredIndex / (pointsCount - 1)) * (graphWidth / width) * 100}%`,
                transform: 'translateX(-50%)',
              }}
            >
              <div className="flex items-center space-x-1.5 text-slate-300 border-b border-slate-700/80 pb-1">
                <Calendar className="w-3.5 h-3.5 text-blue-400" />
                <span className="font-semibold">{formatFullDate(dates[hoveredIndex])}</span>
              </div>
              <div className="flex items-center justify-between space-x-3 pt-0.5">
                <span className="text-slate-400">New Members:</span>
                <strong className="text-blue-400 font-bold text-sm">
                  {numericCounts[hoveredIndex]}
                </strong>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default AnalyticsGrowthChart;
