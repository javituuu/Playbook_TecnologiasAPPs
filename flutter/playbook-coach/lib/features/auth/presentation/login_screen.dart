import 'package:material_ui/material_ui.dart';

import '../../../app/theme/playbook_theme.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/widgets/playbook_brand_mark.dart';
import '../data/mock_auth_data_source.dart';
import 'auth_controller.dart';

/// Pantalla de inicio de sesión exclusiva para Coaches de Playbook.
class LoginScreen extends StatefulWidget {
  const LoginScreen({
    super.key,
    required this.controller,
  });

  final AuthController controller;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  static final RegExp _emailRegex = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');

  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _obscurePassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    if (!(_formKey.currentState?.validate() ?? false)) {
      return;
    }
    await widget.controller.login(
      email: _emailController.text.trim(),
      password: _passwordController.text,
    );
  }

  Future<void> _quickMockLogin() async {
    FocusScope.of(context).unfocus();
    await widget.controller.loginMockDemo();
  }

  void _onFieldChanged(String _) {
    widget.controller.clearError();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 28),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: ListenableBuilder(
                listenable: widget.controller,
                builder: (context, _) {
                  final controller = widget.controller;
                  final isBusy = controller.isBusy;

                  return Card(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Form(
                        key: _formKey,
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: <Widget>[
                            Row(
                              children: <Widget>[
                                const PlaybookBrandMark(size: 44),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: <Widget>[
                                      Text(
                                        'Playbook Coach',
                                        style: theme.textTheme.titleLarge
                                            ?.copyWith(
                                          fontWeight: FontWeight.w700,
                                          color: PlaybookTheme.carbon,
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        'Panel técnico de disponibilidad, prácticas y nóminas',
                                        style: theme.textTheme.bodySmall
                                            ?.copyWith(
                                          color: PlaybookTheme.coolSteel,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 24),
                            TextFormField(
                              key: const Key('login_email_field'),
                              controller: _emailController,
                              enabled: !isBusy,
                              keyboardType: TextInputType.emailAddress,
                              textInputAction: TextInputAction.next,
                              autofillHints: const <String>[
                                AutofillHints.email,
                              ],
                              onChanged: _onFieldChanged,
                              decoration: InputDecoration(
                                labelText: 'Correo electrónico',
                                hintText: 'coach.martinez@udd.cl',
                                prefixIcon: const Icon(Icons.mail_outline),
                                errorText: controller.fieldError('email'),
                              ),
                              validator: (value) {
                                final text = value?.trim() ?? '';
                                if (text.isEmpty) {
                                  return 'Ingresa tu correo electrónico.';
                                }
                                if (!_emailRegex.hasMatch(text)) {
                                  return 'Ingresa un correo electrónico válido.';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 16),
                            TextFormField(
                              key: const Key('login_password_field'),
                              controller: _passwordController,
                              enabled: !isBusy,
                              obscureText: _obscurePassword,
                              textInputAction: TextInputAction.done,
                              autofillHints: const <String>[
                                AutofillHints.password,
                              ],
                              onChanged: _onFieldChanged,
                              onFieldSubmitted: (_) => _submit(),
                              decoration: InputDecoration(
                                labelText: 'Contraseña',
                                hintText: 'Ingresa tu contraseña',
                                prefixIcon: const Icon(Icons.lock_outline),
                                errorText: controller.fieldError('password'),
                                suffixIcon: IconButton(
                                  tooltip: _obscurePassword
                                      ? 'Mostrar contraseña'
                                      : 'Ocultar contraseña',
                                  onPressed: () {
                                    setState(() {
                                      _obscurePassword = !_obscurePassword;
                                    });
                                  },
                                  icon: Icon(
                                    _obscurePassword
                                        ? Icons.visibility_outlined
                                        : Icons.visibility_off_outlined,
                                  ),
                                ),
                              ),
                              validator: (value) {
                                final text = value ?? '';
                                if (text.trim().isEmpty) {
                                  return 'Ingresa tu contraseña.';
                                }
                                if (text.length < 6) {
                                  return 'La contraseña debe tener al menos 6 caracteres.';
                                }
                                return null;
                              },
                            ),
                            if (controller.errorMessage != null) ...<Widget>[
                              const SizedBox(height: 16),
                              _LoginErrorBanner(
                                key: const Key('login_error_banner'),
                                errorType: controller.errorType,
                                statusCode: controller.errorStatusCode,
                                message: controller.errorMessage!,
                                retryAfterSeconds:
                                    controller.retryAfterSeconds,
                              ),
                            ],
                            const SizedBox(height: 20),
                            FilledButton(
                              key: const Key('login_submit_button'),
                              onPressed: isBusy ? null : _submit,
                              child: isBusy
                                  ? const SizedBox(
                                      height: 20,
                                      width: 20,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2.2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Text('Iniciar sesión'),
                            ),
                            const SizedBox(height: 20),
                            const Divider(),
                            const SizedBox(height: 12),
                            Row(
                              children: <Widget>[
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: <Widget>[
                                      Text(
                                        'Entorno de pruebas (Mock)',
                                        style: theme.textTheme.labelLarge
                                            ?.copyWith(
                                          color: PlaybookTheme.carbon,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                      Text(
                                        controller.isMockMode
                                            ? 'Simula login, 401, 403 jugador y 429'
                                            : 'Conectado a API remota',
                                        style: theme.textTheme.bodySmall
                                            ?.copyWith(
                                          color: PlaybookTheme.coolSteel,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                Switch(
                                  key: const Key('login_mock_switch'),
                                  value: controller.isMockMode,
                                  onChanged: isBusy
                                      ? null
                                      : controller.setMockMode,
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),
                            OutlinedButton.icon(
                              key: const Key('login_mock_demo_button'),
                              onPressed: isBusy ? null : _quickMockLogin,
                              icon: const Icon(Icons.bolt_rounded),
                              label: const Text('Acceso rápido Coach (Mock)'),
                            ),
                            if (controller.isMockMode) ...<Widget>[
                              const SizedBox(height: 12),
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: PlaybookColors.surfaceMuted,
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(
                                    color: PlaybookColors.border,
                                  ),
                                ),
                                child: Text(
                                  'Cuentas mock (clave: ${MockAuthDataSource.demoCoachPassword}):\n'
                                  '• Coach: ${MockAuthDataSource.demoCoachEmail}\n'
                                  '• Jugador (403): ${MockAuthDataSource.demoPlayerEmail}\n'
                                  '• Límite (429): ${MockAuthDataSource.rateLimitedEmail}',
                                  style: theme.textTheme.bodySmall?.copyWith(
                                    color: PlaybookTheme.dustyGrape,
                                    height: 1.4,
                                  ),
                                ),
                              ),
                            ] else ...<Widget>[
                              const SizedBox(height: 12),
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: PlaybookColors.surfaceMuted,
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(
                                    color: PlaybookColors.border,
                                  ),
                                ),
                                child: Text(
                                  'Cuentas backend (clave: password):\n'
                                  '• Coach LoL: coach.martinez@udd.cl (o coach@udd.cl)\n'
                                  '• Coach Valorant: coach.rojas@udd.cl',
                                  style: theme.textTheme.bodySmall?.copyWith(
                                    color: PlaybookTheme.dustyGrape,
                                    height: 1.4,
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _LoginErrorBanner extends StatelessWidget {
  const _LoginErrorBanner({
    super.key,
    required this.errorType,
    required this.statusCode,
    required this.message,
    required this.retryAfterSeconds,
  });

  final ApiErrorType? errorType;
  final int? statusCode;
  final String message;
  final int? retryAfterSeconds;

  @override
  Widget build(BuildContext context) {
    final config = _resolvePresentation(errorType, statusCode);

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: config.background,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: config.border),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Icon(config.icon, color: config.foreground, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  config.title,
                  style: TextStyle(
                    color: config.foreground,
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  message,
                  style: TextStyle(
                    color: config.foreground,
                    fontSize: 12.5,
                    height: 1.35,
                  ),
                ),
                if (errorType == ApiErrorType.rateLimited &&
                    retryAfterSeconds != null &&
                    retryAfterSeconds! > 0) ...<Widget>[
                  const SizedBox(height: 4),
                  Text(
                    'Espera sugerida: $retryAfterSeconds s',
                    style: TextStyle(
                      color: config.foreground,
                      fontWeight: FontWeight.w600,
                      fontSize: 12,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  _ErrorBannerStyle _resolvePresentation(ApiErrorType? type, int? code) {
    switch (type) {
      case ApiErrorType.forbidden:
        return _ErrorBannerStyle(
          title: 'Acceso restringido (${code ?? 403})',
          icon: Icons.block_rounded,
          background: PlaybookTheme.heatmap0.background,
          foreground: PlaybookTheme.heatmap0.foreground,
          border: PlaybookTheme.heatmap0.border,
        );
      case ApiErrorType.rateLimited:
        return _ErrorBannerStyle(
          title: 'Demasiados intentos (${code ?? 429})',
          icon: Icons.timer_outlined,
          background: PlaybookTheme.heatmap2.background,
          foreground: PlaybookTheme.heatmap2.foreground,
          border: PlaybookTheme.heatmap2.border,
        );
      case ApiErrorType.unauthorized:
        return _ErrorBannerStyle(
          title: 'Credenciales o sesión inválida (${code ?? 401})',
          icon: Icons.lock_outline_rounded,
          background: PlaybookTheme.heatmap1.background,
          foreground: PlaybookTheme.heatmap1.foreground,
          border: PlaybookTheme.heatmap1.border,
        );
      case ApiErrorType.validation:
        return _ErrorBannerStyle(
          title: 'Revisa los datos ingresados (${code ?? 422})',
          icon: Icons.error_outline_rounded,
          background: PlaybookTheme.heatmap2.background,
          foreground: PlaybookTheme.heatmap2.foreground,
          border: PlaybookTheme.heatmap2.border,
        );
      default:
        return _ErrorBannerStyle(
          title: code != null ? 'Error HTTP $code' : 'No se pudo iniciar sesión',
          icon: Icons.wifi_off_rounded,
          background: PlaybookTheme.heatmap1.background,
          foreground: PlaybookTheme.heatmap1.foreground,
          border: PlaybookTheme.heatmap1.border,
        );
    }
  }
}

class _ErrorBannerStyle {
  const _ErrorBannerStyle({
    required this.title,
    required this.icon,
    required this.background,
    required this.foreground,
    required this.border,
  });

  final String title;
  final IconData icon;
  final Color background;
  final Color foreground;
  final Color border;
}
