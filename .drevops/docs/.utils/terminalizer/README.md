# Rendering terminal sessions for documentation as GIFs with Terminalizer

[Terminalizer](https://terminalizer.com/) is a tool that allows you to record
your terminal sessions and render them as animated GIFs.

## Installation

```shell
npm install -g terminalizer
```

## Usage

### Record and render

1. Record a terminal session:
```shell
terminalizer record <recording-name>
```

2. Update produced YAML file with settings from `example.yml`.

3. Render
```shell
terminalizer render <recording-file>
```
