import { useState } from 'react';
import { Link } from 'react-router-dom';
import { BarChart3 } from 'lucide-react';

export function TrendAreaChart({
  dates = [],
  memberTrend = [],
  postsTrend = [],
}) {
  const [hoveredIndex, setHoveredIndex] = useState(null);

  // Fallback defaults if no data
  const defaultDates = ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7', 'Day 8', 'Day 9', 'Day 10', 'Day 11', 'Day 12', 'Day 13', 'Day 14'];
  const chartLabels = dates.length > 0 ? dates : defaultDates;
  const memberData = memberTrend.length > 0 ? memberTrend : [12, 19, 15, 27, 34, 28, 42, 38, 55, 48, 62, 58, 70, 65];
  const postData = postsTrend.length > 0 ? postsTrend : [24, 30, 28, 45, 52, 40, 68, 59, 82, 74, 95, 88, 110, 102];

  const maxVal = Math.max(...memberData, ...postData, 10);
  const width = 800;
  const height = 240;
  const paddingX = 40;
  const paddingY = 30;
  const graphWidth = width - paddingX * 2;
  const graphHeight = height - paddingY * 2;

  const pointsCount = chartLabels.length;
  const getX = (index) => paddingX + (index / (pointsCount - 1)) * graphWidth;
  const getY = (val) => height - paddingY - (val / maxVal) * graphHeight;

  // Build SVG Path
  const buildSmoothPath = (data) => {
    if (data.length === 0) return '';
    return data.reduce((acc, val, i, arr) => {
      const x = getX(i);
      const y = getY(val);
      if (i === 0) return `M ${x},${y}`;
      const prevX = getX(i - 1);
      const prevY = getY(arr[i - 1]);
      const cpX1 = prevX + (x - prevX) / 2;
      const cpX2 = prevX + (x - prevX) / 2;
      return `${acc} C ${cpX1},${prevY} ${cpX2},${y} ${x},${y}`;
    }, '');
  };

  const memberPath = buildSmoothPath(memberData);
  const memberAreaPath = `${memberPath} L ${getX(pointsCount - 1)},${height - paddingY} L ${getX(0)},${height - paddingY} Z`;

  const postPath = buildSmoothPath(postData);
  const postAreaPath = `${postPath} L ${getX(pointsCount - 1)},${height - paddingY} L ${getX(0)},${height - paddingY} Z`;

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between h-full">
      {/* Header */}
      <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
        <div>
          <h4 className="text-sm font-bold text-slate-800">Platform Activity & Registration Trend</h4>
          <p className="text-xs text-slate-400 mt-0.5">Real-time daily onboarding & timeline activity over past 14 days</p>
        </div>
        <div className="flex items-center space-x-3">
          {/* Legend */}
          <div className="flex items-center space-x-3 text-xs">
            <span className="flex items-center text-slate-600 font-medium">
              <span className="w-2.5 h-2.5 rounded-full bg-blue-600 mr-1.5" /> New Members
            </span>
            <span className="flex items-center text-slate-600 font-medium">
              <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1.5" /> Posts
            </span>
          </div>
          <Link
            to="/admin/analytics"
            className="px-2.5 py-1 text-xs bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-md font-medium border border-slate-200 inline-flex items-center space-x-1"
          >
            <BarChart3 className="w-3.5 h-3.5 text-blue-600" />
            <span>Full Analytics</span>
          </Link>
        </div>
      </div>

      {/* Interactive Chart Container */}
      <div className="p-4 relative">
        <svg
          viewBox={`0 0 ${width} ${height}`}
          className="w-full h-64 overflow-visible"
          onMouseLeave={() => setHoveredIndex(null)}
        >
          <defs>
            <linearGradient id="memberGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor="#176bff" stopOpacity="0.35" />
              <stop offset="100%" stopColor="#176bff" stopOpacity="0.0" />
            </linearGradient>
            <linearGradient id="postGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor="#10b981" stopOpacity="0.25" />
              <stop offset="100%" stopColor="#10b981" stopOpacity="0.0" />
            </linearGradient>
          </defs>

          {/* Grid lines */}
          {[0, 0.25, 0.5, 0.75, 1].map((pct, i) => {
            const y = height - paddingY - pct * graphHeight;
            return (
              <g key={i}>
                <line
                  x1={paddingX}
                  y1={y}
                  x2={width - paddingX}
                  y2={y}
                  stroke="#f1f5f9"
                  strokeDasharray="4 4"
                  strokeWidth="1"
                />
                <text
                  x={paddingX - 8}
                  y={y + 3}
                  textAnchor="end"
                  className="text-[10px] fill-slate-400 font-sans"
                >
                  {Math.round(pct * maxVal)}
                </text>
              </g>
            );
          })}

          {/* Area Gradients */}
          <path d={postAreaPath} fill="url(#postGrad)" />
          <path d={memberAreaPath} fill="url(#memberGrad)" />

          {/* Trend Lines */}
          <path
            d={postPath}
            fill="none"
            stroke="#10b981"
            strokeWidth="2.5"
            strokeLinecap="round"
          />
          <path
            d={memberPath}
            fill="none"
            stroke="#176bff"
            strokeWidth="2.5"
            strokeLinecap="round"
          />

          {/* Interactive Hover Points & Tooltip */}
          {chartLabels.map((lbl, idx) => {
            const x = getX(idx);
            const mY = getY(memberData[idx]);
            const pY = getY(postData[idx]);
            const isHovered = hoveredIndex === idx;

            return (
              <g key={idx} className="cursor-pointer" onMouseEnter={() => setHoveredIndex(idx)}>
                {/* Invisible hover trigger column */}
                <rect
                  x={x - graphWidth / pointsCount / 2}
                  y={paddingY}
                  width={graphWidth / pointsCount}
                  height={graphHeight}
                  fill="transparent"
                />

                {/* X-axis label */}
                <text
                  x={x}
                  y={height - 10}
                  textAnchor="middle"
                  className={`text-[10px] font-sans ${isHovered ? 'fill-blue-600 font-bold' : 'fill-slate-400'}`}
                >
                  {lbl}
                </text>

                {/* Active hover crosshair line */}
                {isHovered && (
                  <line
                    x1={x}
                    y1={paddingY}
                    x2={x}
                    y2={height - paddingY}
                    stroke="#cbd5e1"
                    strokeDasharray="3 3"
                    strokeWidth="1.5"
                  />
                )}

                {/* Circles on dots */}
                <circle
                  cx={x}
                  cy={mY}
                  r={isHovered ? 5 : 3}
                  fill="#ffffff"
                  stroke="#176bff"
                  strokeWidth="2"
                />
                <circle
                  cx={x}
                  cy={pY}
                  r={isHovered ? 5 : 3}
                  fill="#ffffff"
                  stroke="#10b981"
                  strokeWidth="2"
                />
              </g>
            );
          })}
        </svg>

        {/* Hover Tooltip Overlay */}
        {hoveredIndex !== null && (
          <div
            className="absolute top-6 pointer-events-none bg-slate-900 text-white rounded-lg p-2.5 text-xs shadow-xl border border-slate-700 z-20 flex flex-col space-y-1"
            style={{
              left: `${(hoveredIndex / (pointsCount - 1)) * 80 + 10}%`,
              transform: 'translateX(-50%)',
            }}
          >
            <span className="font-bold text-slate-300 border-b border-slate-700 pb-1">
              {chartLabels[hoveredIndex]}
            </span>
            <div className="flex items-center justify-between space-x-3 text-blue-400">
              <span>New Members:</span>
              <strong className="text-white">{memberData[hoveredIndex]}</strong>
            </div>
            <div className="flex items-center justify-between space-x-3 text-emerald-400">
              <span>Posts Created:</span>
              <strong className="text-white">{postData[hoveredIndex]}</strong>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default TrendAreaChart;
