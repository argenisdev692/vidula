import * as React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import type { AuthPageProps } from '@/types/auth';
import {
  Area,
  AreaChart,
  Pie,
  PieChart,
  XAxis,
  YAxis,
  CartesianGrid,
  Label
} from "recharts";

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/shadcn/card";

import {
  ChartConfig,
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
} from "@/shadcn/chart";

// ══════════════════════════════════════════════════════════════════
// Types & Data
// ══════════════════════════════════════════════════════════════════

interface MetricCard {
  title: string;
  value: string;
  change: string;
  changeType: 'positive' | 'negative' | 'neutral';
  icon: string;
  gradient: string;
}

const METRIC_CARDS: MetricCard[] = [
  {
    title: 'Total Users',
    value: '1,284',
    change: '+12.5%',
    changeType: 'positive',
    icon: 'users',
    gradient: 'linear-gradient(135deg, var(--color-chart-1) 0%, oklch(0.5 0.2 264) 100%)',
  },
  {
    title: 'Active Claims',
    value: '347',
    change: '+8.2%',
    changeType: 'positive',
    icon: 'file',
    gradient: 'linear-gradient(135deg, var(--color-chart-2) 0%, oklch(0.6 0.15 162) 100%)',
  },
  {
    title: 'Revenue',
    value: '$48,520',
    change: '-2.4%',
    changeType: 'negative',
    icon: 'dollar',
    gradient: 'linear-gradient(135deg, var(--color-chart-3) 0%, oklch(0.6 0.2 70) 100%)',
  },
  {
    title: 'Completion Rate',
    value: '94.2%',
    change: '+1.8%',
    changeType: 'positive',
    icon: 'check',
    gradient: 'linear-gradient(135deg, var(--color-chart-4) 0%, oklch(0.5 0.25 303) 100%)',
  },
];

const REVENUE_DATA = [
  { month: "Jan", revenue: 15400, target: 14000 },
  { month: "Feb", revenue: 22100, target: 18000 },
  { month: "Mar", revenue: 18500, target: 20000 },
  { month: "Apr", revenue: 28900, target: 25000 },
  { month: "May", revenue: 35200, target: 30000 },
  { month: "Jun", revenue: 48520, target: 40000 },
];

const TASK_STATUS_DATA = [
  { status: "Backlog", count: 12, fill: "var(--color-chart-1)" },
  { status: "To Do", count: 8, fill: "var(--color-chart-2)" },
  { status: "In Progress", count: 5, fill: "var(--color-chart-3)" },
  { status: "Done", count: 14, fill: "var(--color-chart-4)" },
];

const USER_DIST_DATA = [
  { role: "Contractors", count: 45, fill: "var(--color-chart-4)" },
  { role: "Clients", count: 30, fill: "var(--color-chart-1)" },
  { role: "Managers", count: 15, fill: "var(--color-chart-2)" },
  { role: "Admins", count: 10, fill: "var(--color-chart-5)" },
];

const revenueChartConfig = {
  revenue: {
    label: "Revenue",
    color: "var(--color-chart-4)",
  },
  target: {
    label: "Target",
    color: "var(--color-chart-5)",
  },
} satisfies ChartConfig;

const taskChartConfig = {
  count: { label: "Tasks" },
  Backlog: { label: "Backlog", color: "var(--color-chart-1)" },
  "To Do": { label: "To Do", color: "var(--color-chart-2)" },
  "In Progress": { label: "In Progress", color: "var(--color-chart-3)" },
  Done: { label: "Done", color: "var(--color-chart-4)" },
} satisfies ChartConfig;

const userChartConfig = {
  count: { label: "Users" },
  Contractors: { label: "Contractors", color: "var(--color-chart-4)" },
  Clients: { label: "Clients", color: "var(--color-chart-1)" },
  Managers: { label: "Managers", color: "var(--color-chart-2)" },
  Admins: { label: "Admins", color: "var(--color-chart-5)" },
} satisfies ChartConfig;

// ══════════════════════════════════════════════════════════════════
// Metric Card Icon
// ══════════════════════════════════════════════════════════════════

// ══════════════════════════════════════════════════════════════════
// Metric Card Icon
// ══════════════════════════════════════════════════════════════════

function CardIcon({ name }: { name: string }): React.JSX.Element {
  const p = { width: 22, height: 22, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const };

  switch (name) {
    case 'users':
      return (<svg {...p}><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 00-3-3.87" /><path d="M16 3.13a4 4 0 010 7.75" /></svg>);
    case 'file':
      return (<svg {...p}><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /></svg>);
    case 'dollar':
      return (<svg {...p}><line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" /></svg>);
    case 'check':
      return (<svg {...p}><path d="M22 11.08V12a10 10 0 11-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg>);
    default:
      return <></>;
  }
}

// ══════════════════════════════════════════════════════════════════
// Premium Metric Card Component (2026 Edition)
// ══════════════════════════════════════════════════════════════════

interface MetricCardProps {
  card: MetricCard;
}

function PremiumMetricCard({ card }: MetricCardProps): React.JSX.Element {
  return (
    <div
      className="group relative flex flex-col overflow-hidden rounded-2xl border border-white/5 p-6 transition-all duration-500 hover:-translate-y-2"
      style={{
        background: 'linear-gradient(145deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%)',
        backdropFilter: 'blur(12px)',
      }}
    >
      {/* Background Glow Effect */}
      <div 
        className="absolute inset-0 -z-10 opacity-0 transition-opacity duration-500 group-hover:opacity-10"
        style={{ background: card.gradient }}
      />
      
      {/* Top Border Glow */}
      <div 
        className="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-white/20 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"
      />

      <div className="flex items-center justify-between">
        <div 
          className="flex h-12 w-12 items-center justify-center rounded-xl transition-all duration-500 group-hover:scale-110 group-hover:rotate-3 shadow-lg"
          style={{ 
            background: card.gradient,
            boxShadow: '0 8px 16px -4px rgba(0,0,0,0.3)'
          }}
        >
          <div className="text-white">
            <CardIcon name={card.icon} />
          </div>
        </div>
        
        <div 
          className="flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold tracking-tight shadow-sm"
          style={{
            background: card.changeType === 'positive' 
              ? 'rgba(16, 217, 136, 0.1)' 
              : card.changeType === 'negative' 
                ? 'rgba(244, 63, 94, 0.1)' 
                : 'rgba(255, 255, 255, 0.05)',
            color: card.changeType === 'positive' 
              ? 'var(--accent-success)' 
              : card.changeType === 'negative' 
                ? 'var(--accent-error)' 
                : 'var(--text-muted)',
            border: `1px solid \${
              card.changeType === 'positive' 
                ? 'rgba(16, 217, 136, 0.15)' 
                : card.changeType === 'negative' 
                  ? 'rgba(244, 63, 94, 0.15)' 
                  : 'rgba(255, 255, 255, 0.1)'
            }`
          }}
        >
          {card.changeType === 'positive' ? '↑' : card.changeType === 'negative' ? '↓' : '•'}
          {card.change.replace(/[+-]/, '')}
        </div>
      </div>

      <div className="mt-5">
        <p className="text-xs font-medium uppercase tracking-widest text-cyan-400/70" style={{ color: 'var(--text-secondary)' }}>
          {card.title}
        </p>
        <div className="flex items-baseline gap-2 mt-1">
          <h3 className="text-3xl font-black tracking-tighter text-white">
            {card.value}
          </h3>
          <span className="text-[10px] font-medium text-white/30 uppercase tracking-widest">
            USD
          </span>
        </div>
      </div>

      {/* Decorative Wave/Ambient Light (2026 aesthetics) */}
      <div 
        className="absolute -bottom-10 -right-10 h-32 w-32 rounded-full blur-[60px] opacity-20 transition-all duration-700 group-hover:scale-150 group-hover:opacity-40"
        style={{ background: card.gradient }}
      />
    </div>
  );
}

// ══════════════════════════════════════════════════════════════════
// Dashboard Page
// ══════════════════════════════════════════════════════════════════

export default function DashboardPage(): React.JSX.Element {
  const { auth } = usePage<AuthPageProps>().props;

  return (
    <>
      <Head title="Dashboard — Vidula" />
      <AppLayout>
          {/* ── Header ── */}
          <div className="mb-6">
            <h1 className="text-xl font-bold md:text-2xl" style={{ color: 'var(--text-primary)', fontFamily: 'var(--font-sans)' }}>
              Welcome back, {auth.user?.name ?? 'User'} 👋
            </h1>
            <p className="mt-1 text-sm" style={{ color: 'var(--text-muted)', fontFamily: 'var(--font-sans)' }}>
              Here's your projects and revenue overview for today.
            </p>
          </div>

          {/* ═══════════════════════════════════════
              METRIC CARDS (Upgraded to Modern 2026 Style)
              ═══════════════════════════════════════ */}
          <div className="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
            {METRIC_CARDS.map((card) => (
              <PremiumMetricCard key={card.title} card={card} />
            ))}
          </div>

          {/* ═══════════════════════════════════════
              DASHBOARD CHARTS (Replaces Kanban)
              ═══════════════════════════════════════ */}
          <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
            
            {/* Linear Chart - Wide (Takes 2 columns on desktop) */}
            <Card className="col-span-1 md:col-span-2 shadow-sm border-border bg-card">
              <CardHeader>
                <CardTitle>Revenue & Targets</CardTitle>
                <CardDescription>
                  Tracking revenue growth for the last 6 months
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ChartContainer config={revenueChartConfig} className="h-[300px] w-full">
                  <AreaChart
                    accessibilityLayer
                    data={REVENUE_DATA}
                    margin={{ left: 12, right: 12 }}
                  >
                    <CartesianGrid vertical={false} strokeDasharray="3 3" />
                    <XAxis
                      dataKey="month"
                      tickLine={false}
                      axisLine={false}
                      tickMargin={8}
                    />
                    <YAxis
                      tickLine={false}
                      axisLine={false}
                      tickMargin={8}
                      tickFormatter={(value) => `$${value}`}
                    />
                    <ChartTooltip
                      cursor={false}
                      content={<ChartTooltipContent indicator="dot" />}
                    />
                    <Area
                      dataKey="target"
                      type="monotone"
                      fill="var(--color-chart-5)"
                      fillOpacity={0.1}
                      stroke="var(--color-chart-5)"
                      strokeWidth={2}
                    />
                    <Area
                      dataKey="revenue"
                      type="monotone"
                      fill="var(--color-chart-4)"
                      fillOpacity={0.4}
                      stroke="var(--color-chart-4)"
                      strokeWidth={2}
                    />
                  </AreaChart>
                </ChartContainer>
              </CardContent>
            </Card>

            <div className="flex flex-col gap-4">
              {/* Circular Chart 1 - Donut */}
              <Card className="flex-1 flex flex-col shadow-sm border-border bg-card">
                <CardHeader className="items-center pb-0">
                  <CardTitle>Task Status</CardTitle>
                  <CardDescription>Current sprint</CardDescription>
                </CardHeader>
                <CardContent className="flex-1 pb-0 mt-4">
                  <ChartContainer
                    config={taskChartConfig}
                    className="mx-auto aspect-4/3 max-h-[220px]"
                  >
                    <PieChart>
                      <ChartTooltip
                        cursor={false}
                        content={<ChartTooltipContent hideLabel />}
                      />
                      <Pie
                        data={TASK_STATUS_DATA}
                        dataKey="count"
                        nameKey="status"
                        innerRadius={50}
                        outerRadius={75}
                        strokeWidth={2}
                        stroke="var(--background)"
                      >
                        <Label
                          content={({ viewBox }) => {
                            if (viewBox && "cx" in viewBox && "cy" in viewBox) {
                              return (
                                <text
                                  x={viewBox.cx}
                                  y={viewBox.cy}
                                  textAnchor="middle"
                                  dominantBaseline="middle"
                                >
                                  <tspan
                                    x={viewBox.cx}
                                    y={viewBox.cy}
                                    className="fill-foreground text-3xl font-bold"
                                  >
                                    39
                                  </tspan>
                                  <tspan
                                    x={viewBox.cx}
                                    y={(viewBox.cy || 0) + 24}
                                    className="fill-muted-foreground text-xs"
                                  >
                                    Total
                                  </tspan>
                                </text>
                              )
                            }
                          }}
                        />
                      </Pie>
                    </PieChart>
                  </ChartContainer>
                </CardContent>
              </Card>

              {/* Circular Chart 2 - Donut */}
              <Card className="flex-1 flex flex-col shadow-sm border-border bg-card">
                <CardHeader className="items-center pb-0">
                  <CardTitle>User Distribution</CardTitle>
                  <CardDescription>Active platform roles</CardDescription>
                </CardHeader>
                <CardContent className="flex-1 pb-0 mt-4">
                  <ChartContainer
                    config={userChartConfig}
                    className="mx-auto aspect-4/3 max-h-[220px]"
                  >
                    <PieChart>
                      <ChartTooltip
                        cursor={false}
                        content={<ChartTooltipContent hideLabel />}
                      />
                      <Pie
                        data={USER_DIST_DATA}
                        dataKey="count"
                        nameKey="role"
                        innerRadius={50}
                        outerRadius={75}
                        strokeWidth={2}
                        stroke="var(--background)"
                      >
                        <Label
                          content={({ viewBox }) => {
                            if (viewBox && "cx" in viewBox && "cy" in viewBox) {
                              return (
                                <text
                                  x={viewBox.cx}
                                  y={viewBox.cy}
                                  textAnchor="middle"
                                  dominantBaseline="middle"
                                >
                                  <tspan
                                    x={viewBox.cx}
                                    y={viewBox.cy}
                                    className="fill-foreground text-3xl font-bold"
                                  >
                                    100
                                  </tspan>
                                  <tspan
                                    x={viewBox.cx}
                                    y={(viewBox.cy || 0) + 24}
                                    className="fill-muted-foreground text-xs"
                                  >
                                    Users
                                  </tspan>
                                </text>
                              )
                            }
                          }}
                        />
                      </Pie>
                    </PieChart>
                  </ChartContainer>
                </CardContent>
              </Card>
            </div>
            
          </div>
      </AppLayout>
    </>
  );
}
